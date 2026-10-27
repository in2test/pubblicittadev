<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $name
 * @property numeric $min_order_total
 * @property numeric $shipping_cost
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @method static Builder<static>|ShippingTier newModelQuery()
 * @method static Builder<static>|ShippingTier newQuery()
 * @method static Builder<static>|ShippingTier query()
 * @method static Builder<static>|ShippingTier whereCreatedAt($value)
 * @method static Builder<static>|ShippingTier whereId($value)
 * @method static Builder<static>|ShippingTier whereMinOrderTotal($value)
 * @method static Builder<static>|ShippingTier whereName($value)
 * @method static Builder<static>|ShippingTier whereShippingCost($value)
 * @method static Builder<static>|ShippingTier whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'min_order_total',
    'shipping_cost',
])]
class ShippingTier extends Model {}
