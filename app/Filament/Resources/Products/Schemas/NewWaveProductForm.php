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
use Closure;
use Filament\Forms\Components\Checkbox;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
                                    ->rules([
                                        fn (ProductAvailabilityService $service) => function (string $attribute, $value, Closure $fail) use ($service) {
                                            if (! $value) {
                                                return;
                                            }
                                            $info = $service->fetchBasicInfo($value);
                                            if (! $info || empty($info['name'])) {
                                                $fail('Il codice NWG non è valido o non esiste nel sistema esterno.');
                                            }
                                        },
                                    ])
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record, ProductAvailabilityService $service) {
                                        if (! $state) {
                                            return;
                                        }
                                        $info = $service->fetchBasicInfo($state);
                                        if ($info && ! empty($info['name'])) {
                                            $set('name', $info['name']);
                                            $set('price', $info['price'] ?? 0);
                                            $set('description', $info['description'] ?? null);

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
                                                "<div class='flex flex-col gap-2 min-w-50' wire:poll.2s>
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
                                        Select::make('parent_id')
                                            ->label('Categoria di appartenenza')
                                            ->relationship(
                                                name: 'parent',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query, ?Model $record) => $query
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
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                                        if (! $state) {
                                            return;
                                        }

                                        // Load the selected placement and prefill its default price.
                                        $placement = PrintPlacement::find($state, ['*']);
                                        if ($placement) {
                                            $set('additional_price', $placement->default_price);
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
                Section::make('Tutte le Immagini (Frontend)')
                    ->description('Anteprima di tutte le immagini visibili sul sito (locali e remote).')
                    ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateRecord))
                    ->schema([
                        Placeholder::make('all_images_preview')
                            ->label('')
                            ->content(function (?Model $record): string|HtmlString {
                                if (! $record instanceof Model) {
                                    return '';
                                }
                                $images = $record->getAllImages();
                                if ($images->isEmpty()) {
                                    return 'Nessuna immagine.';
                                }
                                $colors = Color::pluck('color_name', 'id')->all();
                                $html = '<div class="flex flex-wrap gap-4">';
                                foreach ($images as $img) {
                                    $html .= '<div class="relative group">';
                                    $html .= '<img src="'.$img->medium.'" class="h-32 w-auto rounded border shadow-sm">';
                                    if ($img->is_remote) {
                                        $html .= '<span class="absolute top-1 right-1 bg-blue-500 text-white text-[10px] px-1 rounded shadow uppercase font-bold">API</span>';
                                    }
                                    if (! empty($img->color_ids)) {
                                        $colorNames = array_map(fn ($id) => $colors[$id] ?? 'Sconosciuto', $img->color_ids);
                                        $colorText = implode(', ', $colorNames);
                                        $html .= '<span class="absolute bottom-1 left-1 right-1 bg-gray-900/80 text-white text-[10px] px-1 rounded shadow text-center truncate" title="'.$colorText.'">'.$colorText.'</span>';
                                    } else {
                                        $html .= '<span class="absolute bottom-1 left-1 right-1 bg-gray-500/80 text-white text-[10px] px-1 rounded shadow text-center">Generica</span>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ]),

                Section::make('Caricamento / Cache Immagini')
                    ->description('Carica nuove immagini o visualizza quelle generiche non associate a variazioni.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('')
                            ->collection('images')
                            ->filterMediaUsing(fn (Collection $media) => $media->filter(fn (Media $item) => empty($item->custom_properties['color_ids'])))
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->panelLayout('grid')
                            ->preserveFilenames()
                            ->responsiveImages()
                            ->columnSpanFull(),
                    ]),

                Section::make('Organizzazione per Colore (Solo Override Locali)')
                    ->description('Usa questa sezione SOLO per immagini locali caricate manualmente. Le immagini dell\'API sono già associate automaticamente ai colori corretti.')
                    ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateRecord))
                    ->schema([
                        Repeater::make('media')
                            ->label('')

                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Placeholder::make('preview')
                                            ->label('Anteprima')
                                            ->content(function ($record): string|HtmlString {
                                                if (! $record) {
                                                    return 'N/A';
                                                }
                                                $url = method_exists($record, 'getUrl') ? (
                                                    $record->hasGeneratedConversion('thumbnail') ? $record->getUrl('thumbnail') : $record->getUrl()
                                                ) : '';

                                                return new HtmlString("<img src='{$url}' class='h-32 w-auto rounded border shadow-sm mx-auto'>");
                                            }),
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

                Section::make('Organizzazione Immagini Sincronizzate (API)')
                    ->description('Ordina le immagini dell\'API, cambia i colori assegnati o rimuovi quelle che non vuoi visualizzare.')
                    ->visible(fn ($livewire, ?Model $record): bool => ! ($livewire instanceof CreateRecord) && ! empty($record->remote_images))
                    ->schema([
                        Repeater::make('remote_images')
                            ->label('')
                            // It binds directly to the array attribute 'remote_images'
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Placeholder::make('preview')
                                            ->label('Anteprima')
                                            ->content(function ($state, Get $get): HtmlString {
                                                $url = $get('thumb') ?? $get('url') ?? '';

                                                return new HtmlString("<img src='{$url}' class='h-32 w-auto rounded border shadow-sm mx-auto'>");
                                            }),
                                        Grid::make(1)
                                            ->schema([
                                                Select::make('color_ids')
                                                    ->label('Associa a Colori')
                                                    ->multiple()
                                                    ->options($colorOptions)
                                                    ->preload()
                                                    ->searchable(),
                                                Hidden::make('url'),
                                                Hidden::make('thumb'),
                                                Hidden::make('medium'),
                                            ])
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->grid(2)
                            ->addable(false)
                            ->deletable(true)
                            ->reorderable(true)
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
