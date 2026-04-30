<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Product;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Concerns\HasResources;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables as FilamentTables;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Actions\Button as FormButton;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\TextColumn;
use App\Jobs\CacheProductImagesJob;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProductResource\Pages\ListProducts;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
            ])->actions([
                FormButton::make('cache_images')
                    ->label('Cache Images')
                    ->color('primary')
                    ->action(function (Product $record) {
                        CacheProductImagesJob::dispatch($record);
                    }),
            ])->persistState(false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Name')->limit(50)->sortable(),
                TextColumn::make('sku')->label('SKU')->visible(true),
                TextColumn::make('price')->label('Price')->sortable()->formatStateUsing(function ($state) {
                    if (is_null($state)) { return ''; }
                    return number_format((float) $state, 2);
                }),
                TextColumn::make('is_active')->label('Active'),
            ])
            ->actions([
                Action::make('cache_images')
                    ->label('Cache Images')
                    ->visible(fn ($record): bool => true)
                    ->action(function (Product $record) {
                        CacheProductImagesJob::dispatch($record);
                    })
                    ->color('primary'),
            ])
            ->bulkActions([
                BulkAction::make('cache_images')
                    ->label('Cache Images')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            CacheProductImagesJob::dispatch($record);
                        }
                    })
                    ->color('primary'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/')
        ];
    }
}
