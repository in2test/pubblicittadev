<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Override;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return UserResource::form($schema);
    }
}
