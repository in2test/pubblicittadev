<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property string|null $description
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|CustomizationPoint newModelQuery()
 * @method static Builder<static>|CustomizationPoint newQuery()
 * @method static Builder<static>|CustomizationPoint query()
 * @method static Builder<static>|CustomizationPoint whereCategory($value)
 * @method static Builder<static>|CustomizationPoint whereCreatedAt($value)
 * @method static Builder<static>|CustomizationPoint whereDescription($value)
 * @method static Builder<static>|CustomizationPoint whereDisplayOrder($value)
 * @method static Builder<static>|CustomizationPoint whereId($value)
 * @method static Builder<static>|CustomizationPoint whereName($value)
 * @method static Builder<static>|CustomizationPoint whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'category',
    'description',
    'display_order',
])]
class CustomizationPoint extends Model
{
    use HasFactory;
}
