<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\ProductVariations\ProductVariationResource;
use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Size;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
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
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
