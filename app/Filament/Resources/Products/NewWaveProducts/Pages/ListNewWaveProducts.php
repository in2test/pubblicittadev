<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
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
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nome')
                                ->required(),
                            Select::make('parent_id')
                                ->label('Categoria padre')
                                ->options(Category::pluck('name', 'id'))
                                ->nullable(),
                        ])
                        ->createOptionUsing(fn (array $data): int => Category::create([
                            'name' => $data['name'],
                            'slug' => SlugGenerator::unique(Category::class, $data['name']),
                            'parent_id' => $data['parent_id'] ?? null,
                            'is_active' => true,
                        ])->id),
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
