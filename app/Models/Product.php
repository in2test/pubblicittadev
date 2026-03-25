<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable(['name', 'slug', 'description', 'price', 'category_id'])]
class Product extends Model
{
    //
    public function category()
    {
        return $this->hasOne(Category::class);
    }
}
