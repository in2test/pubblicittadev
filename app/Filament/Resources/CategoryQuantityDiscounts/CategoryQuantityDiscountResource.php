<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryQuantityDiscounts;

use App\Filament\Resources\CategoryQuantityDiscounts\Pages\CreateCategoryQuantityDiscount;
use App\Filament\Resources\CategoryQuantityDiscounts\Pages\EditCategoryQuantityDiscount;
use App\Filament\Resources\CategoryQuantityDiscounts\Pages\ListCategoryQuantityDiscounts;
use App\Filament\Resources\CategoryQuantityDiscounts\Schemas\CategoryQuantityDiscountForm;
use App\Models\CategoryQuantityDiscount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class CategoryQuantityDiscountResource extends Resource
{
    protected static ?string $model = CategoryQuantityDiscount::class;

    protected static UnitEnum|string|null $navigationGroup = 'Catalogo';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $recordTitleAttribute = 'id';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        // Delegate to a dedicated form schema to keep compatibility with Filament versions
        // that expect Filament\Schemas\Schema. See:
        // App\Filament\Resources\CategoryQuantityDiscounts\Schemas\CategoryQuantityDiscountForm
        return CategoryQuantityDiscountForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        // Build per-record actions with fallbacks for Edit/Delete depending on Filament version
        $recordActions = [];
        // Optional View action (only include if available)
        if (class_exists(ViewAction::class)) {
            $recordActions[] = ViewAction::make('view')
                ->label('Vedi')
                ->icon('heroicon-o-tag')
                ->url(fn (CategoryQuantityDiscount $record): string => route('category-quantity-discount.show', ['record' => $record]))
                ->visible(fn (CategoryQuantityDiscount $record): bool => (bool) $record->id)
                ->openUrlInNewTab();
        }
        // Edit action (prefer built-in if available)
        if (class_exists(EditAction::class)) {
            $recordActions[] = EditAction::make();
        } // else skip fallback to keep compatibility
        // Delete action (guarded)
        if (class_exists(DeleteAction::class)) {
            $recordActions[] = DeleteAction::make();
        }

        return $table
            ->columns([
                TextColumn::make('category.name')->label('Categoria'),
                TextColumn::make('min_quantity')->label('Min'),
                TextColumn::make('max_quantity')->label('Max'),
                TextColumn::make('discount_type')->label('Tipo'),
                TextColumn::make('discount_value')->label('Valore'),
            ])
            ->recordActions($recordActions);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListCategoryQuantityDiscounts::route('/'),
            'create' => CreateCategoryQuantityDiscount::route('/create'),
            'edit' => EditCategoryQuantityDiscount::route('/{record}/edit'),
        ];
    }
}
