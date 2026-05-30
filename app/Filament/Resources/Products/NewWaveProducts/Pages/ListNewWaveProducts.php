<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\NewWaveProducts\Pages;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
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

                    Repeater::make('variation_types')
                        ->label('Varianti')
                        ->schema([
                            Select::make('variation_type_id')
                                ->label('Tipo Variante')
                                ->options(VariationType::pluck('name', 'id'))
                                ->required()
                                ->live(),
                            Select::make('variation_option_ids')
                                ->label('Opzioni')
                                ->options(fn (Get $get) => VariationOption::where('variation_type_id', $get('variation_type_id'))->pluck('name', 'id'))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->visible(fn (Get $get) => filled($get('variation_type_id'))),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Aggiungi variante'),
                ])
                ->action(function (array $data) {
                    $skus = array_filter(
                        array_map(trim(...), preg_split('/[\s,;]+/', (string) $data['skus']) ?: []));

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

                            if (! empty($data['variation_types'])) {
                                foreach ($data['variation_types'] as $index => $variationData) {
                                    $variationTypeId = $variationData['variation_type_id'] ?? null;
                                    if (! $variationTypeId) {
                                        continue;
                                    }

                                    $pvt = ProductVariationType::firstOrCreate([
                                        'product_id' => $product->id,
                                        'variation_type_id' => $variationTypeId,
                                    ], [
                                        'sort_order' => $index,
                                        'has_images' => false,
                                        'is_modifier' => true,
                                    ]);

                                    if (! empty($variationData['variation_option_ids'])) {
                                        foreach ($variationData['variation_option_ids'] as $optId) {
                                            ProductVariationOption::firstOrCreate([
                                                'product_variation_type_id' => $pvt->id,
                                                'variation_option_id' => $optId,
                                            ]);
                                        }
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
