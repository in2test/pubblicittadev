<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $name
 * @property numeric $min_order_total
 * @property numeric $shipping_cost
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier whereMinOrderTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier whereShippingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShippingTier whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'min_order_total',
    'shipping_cost',
])]
class ShippingTier extends Model {}
