<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\PrintPlacement;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Override;
use Throwable;

class ListNewWaveProducts extends ListRecords
{
    protected static string $resource = NewWaveProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('batchImport')
                ->label('Importa da SKU')
                ->icon('heroicon-o-queue-list')
                ->color('success')
                ->modalHeading('Importazione Batch NewWave')
                ->modalWidth('lg')
                ->form([
                    Textarea::make('skus')
                        ->label('Codici SKU')
                        ->placeholder('es. NWG-12345, NWG-67890')
                        ->rows(4)
                        ->helperText('Separa i codici con spazi, virgole o punto e virgola.'),

                    Select::make('category_id')
                        ->label('Categoria')
                        ->options(Category::pluck('name', 'id'))
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nome')
                                ->required(),
                            Select::make('parent_id')
                                ->label('Categoria padre')
                                ->options(Category::pluck('name', 'id'))
                                ->nullable(),
                        ]),

                    Select::make('print_placement_ids')
                        ->label('Posizioni di Stampa')
                        ->multiple()
                        ->options(PrintPlacement::pluck('name', 'id'))
                        ->helperText('Seleziona le posizioni di stampa comuni per tutti i prodotti importati.'),
                ])
                ->action(function (array $data) {
                    $skus = array_filter(
                        array_map(trim(...), preg_split('/[\s,;]+/', (string) $data['skus'])));

                    if ($skus === []) {
                        Notification::make()->title('Errore')->body('Inserisci almeno un codice SKU.')->danger()->send();

                        return;
                    }

                    $service = app(ProductAvailabilityService::class);
                    $results = $service->validateSkus(array_values($skus));

                    $imported = 0;
                    $errors = [];
                    $printPlacementIds = $data['print_placement_ids'] ?? [];

                    foreach ($results['valid'] as $sku => $info) {
                        if (Product::where('sku', $sku)->exists()) {
                            continue;
                        }

                        try {
                            $product = Product::create([
                                'name' => $info['name'],
                                'sku' => $sku,
                                'slug' => SlugGenerator::unique(Product::class, $info['name']),
                                'type' => Product::TYPE_NEWWAVE,
                                'price' => $info['price'] ?? 0,
                                'category_id' => $data['category_id'] ?? null,
                                'sync_status' => SyncStatus::Pending,
                                'is_active' => false,
                            ]);

                            if (! empty($printPlacementIds)) {
                                foreach ($printPlacementIds as $placementId) {
                                    $placement = PrintPlacement::find($placementId);
                                    if ($placement) {
                                        $product->productPrintPlacements()->create([
                                            'print_placement_id' => $placementId,
                                            'additional_price' => $placement->default_price ?? 0,
                                        ]);
                                    }
                                }
                            }

                            SyncNewWaveProductJob::dispatch($product->id);
                            $imported++;
                        } catch (Throwable $e) {
                            $errors[] = $sku.': '.$e->getMessage();
                        }
                    }

                    if ($imported > 0) {
                        Notification::make()
                            ->title('Importazione completata')
                            ->body("Importati {$imported} prodotti.")
                            ->success()
                            ->send();
                    }

                    if ($errors !== []) {
                        Notification::make()
                            ->title('Errori')
                            ->body(implode(', ', $errors))
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
