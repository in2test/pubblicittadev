<?php

declare(strict_types=1);

namespace App\Filament\Resources\PortfolioItems\Pages;

use App\Filament\Resources\PortfolioItems\PortfolioItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePortfolioItem extends CreateRecord
{
    protected static string $resource = PortfolioItemResource::class;
}
