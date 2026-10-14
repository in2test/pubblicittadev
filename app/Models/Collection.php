<?php

namespace App\Models;

use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description'])]
#[RouteKey('slug')]

class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory;

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
