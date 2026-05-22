<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\StandardProducts\Pages;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Override;

class CreateStandardProduct extends CreateRecord
{
    use HasWizard;

    protected static string $resource = StandardProductResource::class;

    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_STANDARD;

        return $data;
    }

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
                        ProductForm::getMinAreaField(),
                        ProductForm::getMaxWidthField(),
                        ProductForm::getMaxHeightField(),
                    ]),
                    ProductForm::getVariationTypesField(),
                    ProductForm::getSkusRepeater(),
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
                    Section::make('Personalizzazione Stampa')
                        ->description('Definisci le posizioni e i lati di stampa disponibili per questo prodotto.')
                        ->schema([
                            ProductForm::getPersonalizationTypeField(),
                            ProductForm::getPrintSidesField(),
                            ProductForm::getPrintPlacementsRepeater(),
                        ]),
                ]),
        ];
    }
}
