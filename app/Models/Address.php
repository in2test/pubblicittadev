<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $name
 * @property string $street
 * @property string $city
 * @property string|null $state
 * @property string $zip
 * @property string $country
 * @property string|null $phone
 * @property int $is_default
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $vat_number
 * @property string|null $fiscal_code
 * @property string|null $sdi_code
 * @property string|null $pec_email
 * @property-read User $user
 *
 * @method static AddressFactory factory($count = null, $state = [])
 * @method static Builder<static>|Address newModelQuery()
 * @method static Builder<static>|Address newQuery()
 * @method static Builder<static>|Address query()
 * @method static Builder<static>|Address whereCity($value)
 * @method static Builder<static>|Address whereCountry($value)
 * @method static Builder<static>|Address whereCreatedAt($value)
 * @method static Builder<static>|Address whereFiscalCode($value)
 * @method static Builder<static>|Address whereId($value)
 * @method static Builder<static>|Address whereIsDefault($value)
 * @method static Builder<static>|Address whereName($value)
 * @method static Builder<static>|Address wherePecEmail($value)
 * @method static Builder<static>|Address wherePhone($value)
 * @method static Builder<static>|Address whereSdiCode($value)
 * @method static Builder<static>|Address whereState($value)
 * @method static Builder<static>|Address whereStreet($value)
 * @method static Builder<static>|Address whereType($value)
 * @method static Builder<static>|Address whereUpdatedAt($value)
 * @method static Builder<static>|Address whereUserId($value)
 * @method static Builder<static>|Address whereVatNumber($value)
 * @method static Builder<static>|Address whereZip($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['user_id', 'type', 'name', 'street', 'city', 'state', 'zip', 'country', 'phone', 'vat_number', 'fiscal_code', 'sdi_code', 'pec_email', 'is_default'])]
class Address extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
