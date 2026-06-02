<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'title',
    'description',
    'link',
    'is_featured',
    'sort_order',
])]
class PortfolioItem extends Model implements HasMedia
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory;

    use InteractsWithMedia;
}
