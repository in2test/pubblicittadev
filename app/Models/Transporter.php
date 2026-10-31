<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TransporterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $tracking_url_template Use {tracking_code} as placeholder
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static TransporterFactory factory($count = null, $state = [])
 * @method static Builder<static>|Transporter newModelQuery()
 * @method static Builder<static>|Transporter newQuery()
 * @method static Builder<static>|Transporter query()
 * @method static Builder<static>|Transporter whereCreatedAt($value)
 * @method static Builder<static>|Transporter whereId($value)
 * @method static Builder<static>|Transporter whereName($value)
 * @method static Builder<static>|Transporter whereTrackingUrlTemplate($value)
 * @method static Builder<static>|Transporter whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'tracking_url_template',
])]
class Transporter extends Model
{
    /** @use HasFactory<TransporterFactory> */
    use HasFactory;
}
