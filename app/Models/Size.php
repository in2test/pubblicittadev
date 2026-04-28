<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'size_name',
    'size',
    'size_code',
    'sort_order',
])]
class Size extends Model
{
    use HasFactory;
}
