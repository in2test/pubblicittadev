<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\PrintPlacement;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class NewWaveProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('Codice Prodotto (NWG Product Number)')
                    ->required()
                    ->live(onBlur: true)
                    ->placeholder('es. sanders')
                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record, ProductAvailabilityService $service) {
                        if (! $state) {
                            return;
                        }

                        $info = $service->fetchBasicInfo($state);

                        if ($info && ! empty($info['name'])) {
                            $set('name', $info['name']);
                            $set('price', $info['price']);
                            $set('description', $info['description'] ?? '');

                            // Generate slug from the REAL name instead of the SKU
                            if (! $record?->slug) {
                                $set('slug', SlugGenerator::unique(Product::class, $info['name'], $record));
                            }

                            Notification::make()
                                ->title('Prodotto trovato: '.$info['name'])
                                ->success()
                                ->send();
                        } else {
                            if (! $record?->slug) {
                                $set('slug', SlugGenerator::unique(Product::class, $state, $record));
                            }

                            Notification::make()
                                ->title('Codice non trovato o API non raggiungibile')
                                ->warning()
                                ->send();
                        }
                    }),
                TextInput::make('slug')
                    ->required(),

                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
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
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->createOptionAction(function (Action $action) {
                        $action->modalHeading('Crea Categoria');
                    }),

                TextInput::make('type')
                    ->default(Product::TYPE_NEWWAVE)
                    ->dehydrated()
                    ->hidden(),

                Section::make('Informazioni NWG (Sincronizzate automaticamente)')
                    ->description('Questi dati vengono recuperati dall\'API NewWave ogni volta che il prodotto viene visualizzato sul sito.')
                    ->collapsible()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Prodotto')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Sincronizzato dall\'API...'),
                        TextInput::make('price')
                            ->label('Prezzo Base API')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->prefix('€')
                            ->placeholder('Sincronizzato dall\'API...'),
                        Textarea::make('description')
                            ->label('Descrizione')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull()
                            ->placeholder('Sincronizzata dall\'API...'),
                    ]),

                Section::make('Anteprima Immagini NWG')
                    ->description('Immagini scaricate automaticamente dall\'API.')
                    ->collapsible()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Immagini Cache')
                            ->collection('images')
                            ->multiple()
                            ->image()
                            ->disabled()
                            ->deletable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Personalizzazione Stampa')
                    ->description('Definisci le posizioni e i lati di stampa disponibili per questo prodotto.')
                    ->collapsible()
                    ->schema([
                        Repeater::make('productPrintPlacements')
                            ->relationship('productPrintPlacements')
                            ->label('Posizioni di Stampa')
                            ->schema([
                                Select::make('print_placement_id')
                                    ->label('Posizione')
                                    ->options(PrintPlacement::pluck('name', 'id'))
                                    ->required()
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
