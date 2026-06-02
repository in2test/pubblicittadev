<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransporterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'tracking_url_template',
])]
class Transporter extends Model
{
    /** @use HasFactory<TransporterFactory> */
    use HasFactory;
}
