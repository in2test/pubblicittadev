<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\ProductVariations\ProductVariationResource;
use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Size;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Override;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $relatedResource = ProductVariationResource::class;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('color_id')
                    ->label('Color')
                    ->options(Color::query()->pluck('color_name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('color_name')
                            ->label('Color Name')
                            ->required(),
                        TextInput::make('color_hex')
                            ->label('Hex Code')
                            ->required(),
                    ]),
                Select::make('size_id')
                    ->label('Size')
                    ->options(Size::query()->pluck('size', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('size_name')
                            ->label('Size Name')
                            ->required(),
                        TextInput::make('size')
                            ->label('Size')
                            ->required(),
                    ]),
                Select::make('print_placement_id')
                    ->label('Print Placement')
                    ->options(PrintPlacement::query()->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description'),
                    ]),
                Select::make('print_side_id')
                    ->label('Print Side')
                    ->options(PrintSide::query()->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description'),
                    ]),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                TextInput::make('quantity')
                    ->label('Stock Quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_available')
                    ->label('Available')
                    ->default(true),
            ]);
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('color.color_name')
                    ->label('Color')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('size.size')
                    ->label('Size')
                    ->badge(),
                TextColumn::make('printPlacement.name')
                    ->label('Print Placement')
                    ->badge()
                    ->color('info'),
                TextColumn::make('printSide.name')
                    ->label('Print Side')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable(),
                TextColumn::make('quantity')
                    ->label('Stock')
                    ->numeric(),
                ToggleColumn::make('is_available')
                    ->label('Available'),
            ])
            ->headerActions([
                Action::make('generateVariations')
                    ->label('Genera Varianti')
                    ->icon('heroicon-o-plus-circle')
                    ->modalWidth('4xl')
                    ->schema([
                        Section::make('Seleziona Attributi')
                            ->description('Scegli quali attributi vuoi combinare per le varianti.')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Toggle::make('use_color')->label('Colore')->live(),
                                        Toggle::make('use_size')->label('Taglia')->live(),
                                        Toggle::make('use_placement')->label('Posizione Stampa')->live(),
                                        Toggle::make('use_side')->label('Lato Stampa')->live(),
                                    ]),
                            ]),
                        Section::make('Opzioni Colore')
                            ->visible(fn (Get $get) => $get('use_color'))
                            ->schema([
                                CheckboxList::make('colors')
                                    ->hiddenLabel()
                                    ->options(Color::pluck('color_name', 'id'))
                                    ->columns(3)
                                    ->searchable()
                                    ->required(fn (Get $get) => $get('use_color')),
                            ]),
                        Section::make('Opzioni Taglia')
                            ->visible(fn (Get $get) => $get('use_size'))
                            ->schema([
                                CheckboxList::make('sizes')
                                    ->hiddenLabel()
                                    ->options(Size::pluck('size', 'id'))
                                    ->columns(4)
                                    ->required(fn (Get $get) => $get('use_size')),
                            ]),
                        Section::make('Opzioni Posizione Stampa')
                            ->visible(fn (Get $get) => $get('use_placement'))
                            ->schema([
                                CheckboxList::make('print_placements')
                                    ->hiddenLabel()
                                    ->options(PrintPlacement::pluck('name', 'id'))
                                    ->columns(2)
                                    ->required(fn (Get $get) => $get('use_placement')),
                            ]),
                        Section::make('Opzioni Lato Stampa')
                            ->visible(fn (Get $get) => $get('use_side'))
                            ->schema([
                                CheckboxList::make('print_sides')
                                    ->hiddenLabel()
                                    ->options(PrintSide::pluck('name', 'id'))
                                    ->columns(2)
                                    ->required(fn (Get $get) => $get('use_side')),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $product = $livewire->getOwnerRecord();

                        $colors = (! empty($data['use_color']) && ! empty($data['colors'])) ? $data['colors'] : [null];
                        $sizes = (! empty($data['use_size']) && ! empty($data['sizes'])) ? $data['sizes'] : [null];
                        $placements = (! empty($data['use_placement']) && ! empty($data['print_placements'])) ? $data['print_placements'] : [null];
                        $sides = (! empty($data['use_side']) && ! empty($data['print_sides'])) ? $data['print_sides'] : [null];

                        if (empty($data['use_color']) && empty($data['use_size']) && empty($data['use_placement']) && empty($data['use_side'])) {
                            return;
                        }

                        foreach ($colors as $colorId) {
                            foreach ($sizes as $sizeId) {
                                foreach ($placements as $placementId) {
                                    foreach ($sides as $sideId) {
                                        if ($colorId === null && $sizeId === null && $placementId === null && $sideId === null) {
                                            continue;
                                        }

                                        $product->variations()->firstOrCreate([
                                            'color_id' => $colorId,
                                            'size_id' => $sizeId,
                                            'print_placement_id' => $placementId,
                                            'print_side_id' => $sideId,
                                        ], [
                                            'is_available' => true,
                                            'quantity' => 0,
                                        ]);
                                    }
                                }
                            }
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
