<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryQuantityDiscounts;

use App\Models\CategoryQuantityDiscount;
use Filament\Resources\Resource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\CategoryQuantityDiscounts\Pages\ListCategoryQuantityDiscounts;
use App\Filament\Resources\CategoryQuantityDiscounts\Pages\CreateCategoryQuantityDiscount;
use App\Filament\Resources\CategoryQuantityDiscounts\Pages\EditCategoryQuantityDiscount;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\NumberInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Override;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class CategoryQuantityDiscountResource extends Resource
{
    protected static ?string $model = CategoryQuantityDiscount::class;
    protected static UnitEnum|string|null $navigationGroup = 'Catalogo';
    protected static string|BackedEnum|null $navigationIcon = null;
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        // Delegate to a dedicated form schema to keep compatibility with Filament versions
        // that expect Filament\Schemas\Schema. See:
        // App\Filament\Resources\CategoryQuantityDiscounts\Schemas\CategoryQuantityDiscountForm
        return \App\Filament\Resources\CategoryQuantityDiscounts\Schemas\CategoryQuantityDiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')->label('Categoria'),
                TextColumn::make('min_quantity')->label('Min'),
                TextColumn::make('max_quantity')->label('Max'),
                TextColumn::make('discount_type')->label('Tipo'),
                TextColumn::make('discount_value')->label('Valore'),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategoryQuantityDiscounts::route('/'),
            'create' => CreateCategoryQuantityDiscount::route('/create'),
            'edit' => EditCategoryQuantityDiscount::route('/{record}/edit'),
        ];
    }
}
