<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Enums\SyncStatus;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Filament\Notifications\Notification;
use Livewire\Component;
use Override;
use Throwable;

class BatchImportNewWave extends Component
{
    public string $skus = '';

    public array $validatedProducts = [];

    public array $importResults = [];

    public bool $validated = false;

    public bool $imported = false;

    public array $invalidSkus = [];

    public function validateSkus(ProductAvailabilityService $service): void
    {
        $skuList = array_filter(
            array_map(trim(...), preg_split('/[\s,;]+/', $this->skus))
        );

        if ($skuList === []) {
            Notification::make()
                ->title('Errore')
                ->body('Inserisci almeno un codice SKU.')
                ->danger()
                ->send();

            return;
        }

        $results = $service->validateSkus(array_values($skuList));

        $this->validatedProducts = [];

        foreach ($results['valid'] as $sku => $info) {
            $this->validatedProducts[] = [
                'sku' => $sku,
                'name' => $info['name'],
                'price' => $info['price'],
                'exists' => Product::where('sku', $sku)->exists(),
                'selected' => true,
            ];
        }

        $this->invalidSkus = $results['invalid'];
        $this->validated = true;
        $this->imported = false;
        $this->importResults = [];
    }

    public function importSelected(): void
    {
        $selectedSkus = array_keys(array_filter(
            $this->validatedProducts,
            fn (array $p) => ($p['selected'] ?? false) && ! ($p['exists'] ?? false)
        ));

        if ($selectedSkus === []) {
            Notification::make()
                ->title('Errore')
                ->body('Seleziona almeno un prodotto da importare.')
                ->danger()
                ->send();

            return;
        }

        $imported = [];
        $errors = [];

        foreach ($selectedSkus as $sku) {
            try {
                $idx = array_search($sku, array_column($this->validatedProducts, 'sku'));
                if ($idx === false) {
                    continue;
                }
                $productData = $this->validatedProducts[$idx];

                $product = Product::create([
                    'name' => $productData['name'],
                    'sku' => $sku,
                    'slug' => SlugGenerator::unique(Product::class, $sku),
                    'type' => Product::TYPE_NEWWAVE,
                    'price' => $productData['price'] ?? 0,
                    'sync_status' => SyncStatus::Pending,
                    'is_active' => false,
                ]);

                SyncNewWaveProductJob::dispatch($product);

                $imported[] = $sku;
            } catch (Throwable $e) {
                $errors[] = $sku.': '.$e->getMessage();
            }
        }

        $this->importResults = [
            'imported' => $imported,
            'errors' => $errors,
        ];
        $this->imported = true;
        $this->validated = false;

        Notification::make()
            ->title('Importazione completata')
            ->body('Importati '.count($imported).' prodotti.')
            ->success()
            ->send();
    }

    #[Override]
    public function reset(): void
    {
        $this->skus = '';
        $this->validatedProducts = [];
        $this->validated = false;
        $this->imported = false;
        $this->importResults = [];
    }

    public function render()
    {
        return view('livewire.batch-import-newwave');
    }

    protected function getLayout(): ?string
    {
        return null;
    }
}
