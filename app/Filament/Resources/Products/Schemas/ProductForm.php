<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\Product;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->default(Product::TYPE_STANDARD)
                    ->dehydrated()
                    ->hidden(),
                TextInput::make('name')
                    ->label('Nome Prodotto')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                        $slug = SlugGenerator::unique(Product::class, $state, $record);
                        $set('slug', $slug);
                    })
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->label('Descrizione')
                    ->columnSpanFull(),
                TextInput::make('sku')
                    ->label('Codice Prodotto'),
                TextInput::make('price')
                    ->label('Prezzo')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Toggle::make('is_featured')
                    ->label('Prodotto in Evidenza')
                    ->default(false)
                    ->columnSpanFull()
                    ->required(),
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name') // Load categories
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nome Categoria')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                                $slug = SlugGenerator::unique(Category::class, $state, $record);
                                $set('slug', $slug);
                            })
                            ->required(),
                        TextInput::make('slug')
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrizione'),
                        Select::make('parent_id')
                            ->label('Categoria di appartenenza')
                            ->relationship('parent', 'name') // Load parent categories
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]) // Form for adding new category
                    ->createOptionAction(function (Action $action) {
                        $action->modalHeading('Crea Categoria');
                    }),
                // Image Media Library
                SpatieMediaLibraryFileUpload::make('images')
                    ->label('Caricamento Rapido Immagini')
                    ->collection('images')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->imagePreviewHeight('150')
                    ->panelLayout('grid')
                    ->disk('public')
                    ->conversionsDisk('public')
                    ->customProperties(fn (): array => [
                        'alt' => 'descrizione',
                    ])
                    ->columnSpanFull(),

                Section::make('Organizzazione Galleria per Colore')
                    ->description('Associa le immagini caricate sopra ai colori disponibili per questo prodotto.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('media')
                            ->relationship('media', fn ($query) => $query->where('collection_name', 'images'))
                            ->schema([
                                TextEntry::make('preview')
                                    ->label('Immagine')
                                    ->state(fn ($record) => $record ? new HtmlString("<img src='{$record->getUrl('thumbnail')}' class='h-20 w-auto rounded border shadow-sm'>") : 'Sconosciuta'),
                                Select::make('custom_properties.color_ids')
                                    ->label('Associa a uno o più colori')
                                    ->multiple()
                                    ->options(fn ($record) => Color::pluck('color_name', 'id'))
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
                            ->addable(false) // Only manage existing ones uploaded above
                            ->deletable(false) // Use the uploader above for deletion
                            ->reorderable(false),
                    ])
                    ->columnSpanFull(),

                Section::make('Personalizzazione Stampa')
                    ->description('Definisci le posizioni e i lati di stampa disponibili per questo prodotto.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('printPlacements')
                            ->relationship('printPlacements')
                            ->label('Posizioni di Stampa')
                            ->schema([
                                Select::make('print_placement_id')
                                    ->label('Posizione')
                                    ->options(PrintPlacement::pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                        if ($state) {
                                            $placement = PrintPlacement::find($state);
                                            if ($placement) {
                                                $set('additional_price', $placement->default_price);
                                            }
                                        }
                                    })
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('additional_price')
                                    ->label('Sovrapprezzo (€)')
                                    ->numeric()
                                    ->prefix('+ €')
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->addActionLabel('Aggiungi Posizione'),

                        Select::make('printSides')
                            ->relationship('printSides', 'name')
                            ->label('Lati di Stampa Disponibili')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
