<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\VariationOption;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

final class ProductMediaFields
{
    public static function getImagesField(): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make('images')
            ->label('Caricamento Rapido Immagini')
            ->collection('images')
            ->multiple()
            ->reorderable()
            ->imagePreviewHeight('150')
            ->panelLayout('grid')
            ->disk('public')
            ->conversionsDisk('public')
            ->customProperties(fn ($record, $file): array => [
                'alt' => 'descrizione',
            ])
            ->columnSpanFull();
    }

    public static function getColorGallerySection(): Section
    {
        return Section::make('Organizzazione Galleria per Colore')
            ->description('Associa le immagini caricate sopra ai colori disponibili per questo prodotto.')
            ->collapsible()
            ->schema([
                Repeater::make('media')
                    ->relationship('media', fn ($query) => $query->where('collection_name', 'images'))
                    ->defaultItems(0)
                    ->schema([
                        Placeholder::make('preview')
                            ->label('Immagine')
                            ->content(fn ($record) => $record ? new HtmlString("<img src='{$record->getUrl('thumbnail')}' class='h-20 w-auto rounded border shadow-sm'>") : 'Sconosciuta'),
                        Select::make('custom_properties.variation_option_ids')
                            ->label('Associa a una o più varianti')
                            ->multiple()
                            ->options(fn ($record) => VariationOption::pluck('name', 'id'))
                            ->preload()
                            ->searchable()
                            ->columnSpan(2),
                        TextInput::make('custom_properties.alt')
                            ->label('Testo Alt')
                            ->placeholder('es. Vista laterale')
                            ->columnSpan(2),
                        Checkbox::make('custom_properties.is_manual')
                            ->label('Manuale')
                            ->inline(false),
                    ])
                    ->columns(3)
                    ->grid(2)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ])
            ->columnSpanFull();
    }
}
