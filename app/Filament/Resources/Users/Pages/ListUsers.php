<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Override;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    #[Override]
    public function table(Table $table): Table
    {
        return UserResource::table($table);
    }
}
