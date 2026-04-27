<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Resources\Sizes\SizeResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Override;

class ListSizes extends ListRecords
{
    protected static string $resource = SizeResource::class;

    #[Override]
    public function table(Table $table): Table
    {
        return SizeResource::table($table);
    }
}
