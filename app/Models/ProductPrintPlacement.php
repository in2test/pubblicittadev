<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'print_placement_id', 'additional_price'])]
class ProductPrintPlacement extends Model
{
    protected $table = 'product_print_placement';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function printPlacement(): BelongsTo
    {
        return $this->belongsTo(PrintPlacement::class);
    }
}
