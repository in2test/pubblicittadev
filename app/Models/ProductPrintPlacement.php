<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'print_placement_id', 'additional_price'])]
#[Table(name: 'product_print_placement')]
class ProductPrintPlacement extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function printPlacement(): BelongsTo
    {
        return $this->belongsTo(PrintPlacement::class);
    }
}
