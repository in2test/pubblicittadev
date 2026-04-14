<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\ProductVariations\ProductVariationResource;
use App\Models\Color;
use App\Models\Size;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    protected static ?string $relatedResource = ProductVariationResource::class;

    protected static ?string $title = 'Varianti Prodotto';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('color_id')
                    ->label('Colore')
                    ->options(Color::query()->pluck('color_name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('color_name')
                            ->label('Nome Colore')
                            ->required(),
                        TextInput::make('color_hex')
                            ->label('Codice Hex')
                            ->required(),
                    ]),
                Select::make('size_id')
                    ->label('Taglia')
                    ->options(Size::query()->pluck('size', 'id'))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('size_name')
                            ->label('Nome Taglia')
                            ->required(),
                        TextInput::make('size')
                            ->label('Taglia')
                            ->required(),
                    ]),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                TextInput::make('quantity')
                    ->label('Giacenza in Stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_available')
                    ->label('Disponibile')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('color.color_name')
                    ->label('Colore')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('size.size')
                    ->label('Taglia')
                    ->badge()
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Giacenza')
                    ->numeric(),
                ToggleColumn::make('is_available')
                    ->label('Disponibile'),
            ])
            ->toolbarActions([
                Action::make('generateVariations')
                    ->label('Genera Varianti')
                    ->icon('heroicon-o-plus-circle')
                    ->modalWidth('4xl')
                    ->schema([
                        Section::make('Seleziona Attributi')
                            ->description('Scegli quali attributi vuoi combinare per le varianti.')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Toggle::make('use_color')->label('Colore')->live(),
                                        Toggle::make('use_size')->label('Taglia')->live(),
                                    ]),
                            ]),
                        Section::make('Opzioni Colore')
                            ->visible(fn ($get) => $get('use_color'))
                            ->schema([
                                CheckboxList::make('colors')
                                    ->hiddenLabel()
                                    ->options(Color::pluck('color_name', 'id'))
                                    ->columns(3)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->required(fn ($get) => $get('use_color')),
                            ]),
                        Section::make('Opzioni Taglia')
                            ->visible(fn ($get) => $get('use_size'))
                            ->schema([
                                CheckboxList::make('sizes')
                                    ->hiddenLabel()
                                    ->options(Size::pluck('size', 'id'))
                                    ->columns(4)
                                    ->bulkToggleable()
                                    ->required(fn ($get) => $get('use_size')),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $product = $livewire->getOwnerRecord();

                        $colors = (! empty($data['use_color']) && ! empty($data['colors'])) ? $data['colors'] : [null];
                        $sizes = (! empty($data['use_size']) && ! empty($data['sizes'])) ? $data['sizes'] : [null];

                        if (empty($data['use_color']) && empty($data['use_size'])) {
                            return;
                        }

                        foreach ($colors as $colorId) {
                            $color = $colorId ? Color::find($colorId) : null;
                            $colorCode = $color ? Str::slug($color->color_name) : 'no-color';

                            foreach ($sizes as $sizeId) {
                                $size = $sizeId ? Size::find($sizeId) : null;
                                $sizeCode = $size ? Str::slug($size->size) : 'no-size';

                                if ($colorId === null && $sizeId === null) {
                                    continue;
                                }

                                // BASE_SKU-COLOR-SIZE
                                $variantSku = strtoupper("{$product->sku}-{$colorCode}-{$sizeCode}");

                                $product->variations()->firstOrCreate([
                                    'color_id' => $colorId,
                                    'size_id' => $sizeId,
                                ], [
                                    'sku' => $variantSku,
                                    'is_available' => true,
                                    'quantity' => 0,
                                ]);
                            }
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
