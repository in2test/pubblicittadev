<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\SyncStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class NewWaveProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $colorOptions = Color::pluck('color_name', 'id')->all();

        $generalTab = Tab::make('Configurazione Generale')
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Identificazione')
                            ->columnSpan(1)
                            ->schema([
                                TextInput::make('sku')
                                    ->label('Codice NWG')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record, ProductAvailabilityService $service) {
                                        if (! $state) {
                                            return;
                                        }
                                        $info = $service->fetchBasicInfo($state);
                                        if ($info && ! empty($info['name'])) {
                                            $set('name', $info['name']);
                                            $set('price', $info['price'] ?? 0);

                                            // Ensure slug is generated
                                            if (empty($record?->slug)) {
                                                $set('slug', SlugGenerator::unique(Product::class, $info['name'], $record));
                                            }
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label('Disponibile (Attivo)')
                                    ->disabled(fn (?Model $record) => $record?->sync_status !== SyncStatus::Synced)
                                    ->helperText(fn (?Model $record) => $record?->sync_status !== SyncStatus::Synced ? 'Attivabile solo dopo una sincronizzazione completa con successo.' : 'Attiva per rendere visibile il prodotto.')
                                    ->default(false),
                                Placeholder::make('sync_progress_display')
                                    ->label('Stato Sincronizzazione')
                                    ->content(function (?Model $record) {
                                        if (! $record instanceof Model) {
                                            return 'Non sincronizzato';
                                        }
                                        if ($record->sync_status === SyncStatus::Syncing) {
                                            return new HtmlString(
                                                "<div class='flex flex-col gap-2 min-w-[200px]' wire:poll.2s>
                                                    <span class='text-sm text-gray-700 dark:text-gray-300 font-medium'>In corso... {$record->sync_progress}%</span>
                                                    <div class='w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700'>
                                                        <div class='bg-primary-600 h-2.5 rounded-full transition-all duration-500' style='width: {$record->sync_progress}%'></div>
                                                    </div>
                                                 </div>"
                                            );
                                        }

                                        return $record->sync_status?->getLabel() ?? 'Sconosciuto';
                                    }),
                                Select::make('category_id')
                                    ->label('Categoria')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nome Categoria')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Set $set, ?string $state, ?Model $record) => $set('slug', SlugGenerator::unique(Category::class, $state ?? '', $record))),
                                        TextInput::make('slug')
                                            ->required()
                                            ->unique(Category::class, 'slug'),
                                        Textarea::make('description')
                                            ->label('Descrizione'),
                                        Toggle::make('is_active')
                                            ->label('Attiva')
                                            ->default(true),
                                    ]),
                                Toggle::make('is_featured')
                                    ->label('In Evidenza')
                                    ->default(false),
                            ]),

                        Section::make('Dati API & Overrides')
                            ->columnSpan(2)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nome Prodotto')
                                            ->disabled()
                                            ->dehydrated(),
                                        TextInput::make('price')
                                            ->label('Prezzo Base (€)')
                                            ->disabled(fn (Get $get) => ! $get('override_price'))
                                            ->dehydrated()
                                            ->numeric()
                                            ->prefix('€'),
                                        TextInput::make('offer_price')
                                            ->label('Prezzo Offerta (€)')
                                            ->numeric()
                                            ->prefix('€')
                                            ->helperText('Lascia vuoto se non c\'è offerta. Se impostato, verrà mostrato come prezzo principale.'),
                                        Toggle::make('override_price')
                                            ->label('Sovrascrivi Prezzo Base')
                                            ->live(),
                                        Toggle::make('override_description')
                                            ->label('Sovrascrivi Descrizione')
                                            ->live(),
                                    ]),
                                Textarea::make('description')
                                    ->label('Descrizione')
                                    ->disabled(fn (Get $get) => ! $get('override_description'))
                                    ->dehydrated()
                                    ->rows(4),
                            ]),
                    ]),

                Section::make('Esclusioni & Customizzazioni')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('disabled_colors')
                                    ->label('Nascondi Colori')
                                    ->multiple()
                                    ->options(fn (?Model $record) => $record instanceof Model
                                        ? Color::whereHas('variations', fn ($q) => $q->where('product_id', $record->id))
                                            ->pluck('color_name', 'id')
                                            ->all()
                                        : []
                                    )
                                    ->helperText('Questi colori non saranno visibili o acquistabili.'),
                            ]),
                    ]),

                Section::make('Personalizzazione Stampa')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('productPrintPlacements')
                            ->label('Posizioni di Stampa')
                            ->relationship('productPrintPlacements')
                            ->defaultItems(0)
                            ->addActionLabel('Aggiungi Posizione')
                            ->schema([
                                Select::make('print_placement_id')
                                    ->label('Posizione')
                                    ->options(PrintPlacement::pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if ($state) {
                                            $placement = PrintPlacement::find($state);
                                            if ($placement) {
                                                $set('additional_price', $placement->default_price);
                                            }
                                        }
                                    }),
                                TextInput::make('additional_price')
                                    ->label('Sovrapprezzo')
                                    ->numeric()
                                    ->prefix('+ €'),
                            ])
                            ->columns(2)
                            ->grid(3),
                    ]),
            ]);

        $galleryTab = Tab::make('Galleria & Colori')
            ->icon('heroicon-o-photo')
            ->schema([
                Section::make('Caricamento / Cache Immagini')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('')
                            ->collection('images')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->panelLayout('grid')
                            ->preserveFilenames()
                            ->responsiveImages()
                            ->columnSpanFull(),
                    ]),

                Section::make('Organizzazione per Colore')
                    ->description('Associa ogni immagine ai rispettivi colori per permettere il cambio immagine dinamico sul sito.')
                    ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateRecord))
                    ->schema([
                        Repeater::make('media')
                            ->label('')
                            ->relationship('media', fn ($query) => $query->where('collection_name', 'images'))
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Placeholder::make('preview')
                                            ->label('Anteprima')
                                            ->content(fn ($record) => $record ? new HtmlString("<img src='{$record->getUrl('thumbnail')}' class='h-32 w-auto rounded border shadow-sm mx-auto'>") : 'N/A'),
                                        Grid::make(1)
                                            ->schema([
                                                Select::make('custom_properties.color_ids')
                                                    ->label('Associa a Colori')
                                                    ->multiple()
                                                    ->options($colorOptions)
                                                    ->preload()
                                                    ->searchable(),
                                                TextInput::make('custom_properties.alt')
                                                    ->label('Testo Alt')
                                                    ->placeholder('es. Vista laterale'),
                                                Checkbox::make('custom_properties.is_manual')
                                                    ->label('Gestione Manuale (Blocca Sync API)')
                                                    ->inline(false),
                                            ])
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->grid(2)
                            ->addable(false)
                            ->deletable(false)
                            ->columnSpanFull(),
                    ]),
            ]);

        return $schema
            ->components([
                Tabs::make('Prodotto NewWave')
                    ->tabs([
                        $generalTab,
                        $galleryTab,
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
