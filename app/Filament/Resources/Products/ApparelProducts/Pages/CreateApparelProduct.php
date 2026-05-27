<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\ApparelProducts\Pages;

use App\Enums\ProductClass;
use App\Filament\Resources\Products\ApparelProducts\ApparelProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;
use Override;

class CreateApparelProduct extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ApparelProductResource::class;

    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_STANDARD;
        $data['product_class'] = ProductClass::Apparel;

        return $data;
    }

    /**
     * Define the wizard steps for creating an apparel product.
     *
     * @return array<int, Step>
     */
    protected function getSteps(): array
    {
        return [
            Step::make('Informazioni di Base')
                ->description('Nome, categoria, descrizione e modello di prezzo')
                ->schema([
                    ProductForm::getTypeField(),
                    Grid::make(2)->schema([
                        ProductForm::getNameField(),
                        ProductForm::getSlugField(),
                        ProductForm::getSkuField(),
                        ProductForm::getCategoryField(),
                        ProductForm::getPricingModelField(),
                    ]),
                    ProductForm::getDescriptionField(),
                ]),

            Step::make('Prezzi, Varianti e Inventario')
                ->description('Prezzi base, limiti area, varianti e scaglioni di prezzo')
                ->schema([
                    Grid::make(2)->schema([
                        ProductForm::getPriceField(),
                        ProductForm::getOfferPriceField(),
                    ]),
                    ProductForm::getBaseVariationsRepeater(),
                    ProductForm::getModifiersRepeater(),
                    ProductForm::getPricingTiersRepeater(),
                ]),

            Step::make('Personalizzazione e Stato')
                ->description('Immagini, posizioni di stampa e stato di pubblicazione')
                ->schema([
                    ProductForm::getImagesField(),
                    ProductForm::getColorGallerySection(),
                    Grid::make(2)->schema([
                        ProductForm::getIsActiveField(),
                        ProductForm::getIsFeaturedField(),
                    ]),
                ]),
        ];
    }
}
