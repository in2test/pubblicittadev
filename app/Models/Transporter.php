<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransporterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $tracking_url_template Use {tracking_code} as placeholder
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Database\Factories\TransporterFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter whereTrackingUrlTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Transporter whereUpdatedAt($value)
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
