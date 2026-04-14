<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Vedi Prodotto')
                ->icon('heroicon-o-eye')
                ->url(fn (Product $record): string => route('product', [
                    'category' => $record->category->slug,
                    'slug' => $record->slug,
                ]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
