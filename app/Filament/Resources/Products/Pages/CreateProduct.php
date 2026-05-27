<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use Override;

class CreateProduct extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProductResource::class;

    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_STANDARD;

        // Automatically determine pricing model from product class if not set
        if (! empty($data['product_class'])) {
            if ($data['product_class'] === ProductClass::AreaBased->value) {
                $data['pricing_model'] = 'area';
            } elseif ($data['product_class'] === ProductClass::ItemBased->value) {
                $data['pricing_model'] = 'quantity';
            } else {
                $data['pricing_model'] = 'fixed';
            }
        }

        return $data;
    }

    /**
     * Define the wizard steps for creating a product.
     *
     * @return array<int, Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make('1. Informazioni Generali')
                ->description('Nome, categoria, descrizione e tipo calcolo prezzo')
                ->schema([
                    ProductResource::getTypeField(),
                    Grid::make(2)->schema([
                        ProductResource::getNameField(),
                        ProductResource::getSlugField(),
                        ProductResource::getSkuField(),
                        ProductResource::getCategoryField(),
                        ProductResource::getProductClassField(),
                        ProductResource::getPriceField(),
                        ProductResource::getOfferPriceField(),
                        ProductResource::getIsActiveField(),
                        ProductResource::getIsFeaturedField(),
                    ]),
                    ProductResource::getDescriptionField(),
                    ProductResource::getSheetSettingsSection(),
                ]),

            Step::make('2. Galleria e Varianti')
                ->description('Carica immagini e seleziona le varianti disponibili')
                ->schema([
                    ProductResource::getImagesField(),
                    ProductResource::getBaseVariationsRepeater(),
                ]),

            Step::make('3. Prezzi Varianti')
                ->description('Configura prezzi e scaglioni per ogni combinazione')
                ->schema([
                    ProductResource::getSkusRepeater(),
                ]),

            Step::make('4. Associa Immagini')
                ->description('Associazione immagini alle varianti')
                ->schema(ProductResource::getAssignImagesSection()),
        ];
    }
}
