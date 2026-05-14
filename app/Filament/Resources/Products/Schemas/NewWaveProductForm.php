<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\SyncStatus;
use App\Models\Category;
use App\Models\Color;
use App\Models\Image;
use App\Models\PrintPlacement;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
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
use Filament\Support\Icons\Heroicon;
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
                                    ->unique()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?Product $record, ProductAvailabilityService $service) {
                                        if (! $state) {
                                            return;
                                        }
                                        $info = $service->fetchBasicInfo($state);
                                        if ($info && ! empty($info['name'])) {
                                            $set('name', $info['name']);
                                            $set('price', $info['price'] ?? 0);
                                            $set('description', $info['description'] ?? null);

                                            // Ensure slug is generated
                                            if (empty($record->slug)) {
                                                $set('slug', SlugGenerator::unique(Product::class, $info['name'], $record));
                                            }

                                        }
                                    }),
                                TextInput::make('slug')
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label('Disponibile (Attivo)')
                                    ->disabled(fn (?Product $record) => $record?->sync_status !== SyncStatus::Synced)
                                    ->helperText(fn (?Product $record) => $record?->sync_status !== SyncStatus::Synced ? 'Attivabile solo dopo una sincronizzazione completa con successo.' : 'Attiva per rendere visibile il prodotto.')
                                    ->default(false),
                                Placeholder::make('sync_progress_display')
                                    ->label('Stato Sincronizzazione')
                                    ->content(function (?Product $record) {
                                        if (! $record instanceof Product) {
                                            return 'Non sincronizzato';
                                        }
                                        if ($record->sync_status === SyncStatus::Syncing) {
                                            return new HtmlString(
                                                "<div class='flex flex-col gap-2 min-w-50' wire:poll.2s>
                                                    <span class='text-sm text-gray-700 dark:text-gray-300 font-medium'>In corso... {$record->sync_progress}%</span>
                                                    <div class='w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700'>
                                                        <div class='bg-primary-600 h-2.5 rounded-full transition-all duration-500' style='width: {$record->sync_progress}%'></div>
                                                    </div>
                                                 </div>"
                                            );
                                        }

                                        return $record->getFirstImage()->thumbnail_url ?? ($record->getThumbnailUrl() ?? 'Sconosciuto');
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
                                            ->afterStateUpdated(fn (Set $set, ?string $state, ?Category $record) => $set('slug', SlugGenerator::unique(Category::class, $state ?? '', $record))),
                                        TextInput::make('slug')
                                            ->required()
                                            ->unique(Category::class, 'slug'),
                                        Select::make('parent_id')
                                            ->label('Categoria di appartenenza')
                                            ->relationship(
                                                name: 'parent',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query, ?Category $record) => $query
                                                    ->whereNull('parent_id')
                                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id)),
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
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
                                    ->options(fn (?Product $record) => $record instanceof Model
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
                                            $placement = PrintPlacement::find($state, ['default_price']);
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
                        SpatieMediaLibraryFileUpload::make('media_images')
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

                Section::make('Galleria Immagini')
                    ->description('Visualizza e riordina sia le immagini sincronizzate da NewWave sia quelle caricate localmente.')
                    ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateRecord))
                    ->schema([
                        Repeater::make('remote_images')
                            ->relationship('images')
                            ->orderColumn('order_by')
                            ->grid(5)
                            ->schema([
                                Hidden::make('id'),
                                Placeholder::make('preview')
                                    ->label('Anteprima')
                                    ->content(fn (?Image $record) => $record instanceof Image ? new HtmlString("<img src='".e($record->thumbnailUrl)."' class='h-32 w-auto rounded border shadow-sm mx-auto'>") : 'N/A'),
                                Select::make('color_id')
                                    ->label('Colore associato')
                                    ->options($colorOptions)
                                    ->searchable()
                                    ->nullable()
                                    ->placeholder('Nessuna associazione')
                                    ->columnSpanFull(),
                                TextInput::make('image_url')
                                    ->label('URL immagine')
                                    ->url()
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('image_description')
                                    ->label('Descrizione immagine')
                                    ->placeholder('es. Vista frontale')
                                    ->columnSpanFull(),
                            ])
                            ->extraItemActions([
                                Action::make('downloadToLibrary')
                                    ->label('Scarica in libreria')
                                    ->icon(Heroicon::OutlinedArrowDownTray)
                                    ->action(function (array $arguments, Repeater $component): void {
                                        $item = $component->getItemState($arguments['item']);
                                        $image = Image::query()->find($item['id'] ?? null);

                                        if (! $image) {
                                            return;
                                        }

                                        $image->downloadToMediaLibrary();
                                    }),
                            ])
                            ->addActionLabel('Aggiungi URL immagine')
                            ->columns(1)
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
