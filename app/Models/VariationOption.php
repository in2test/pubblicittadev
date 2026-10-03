<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModifierType;
use App\Services\VariationOptionColorService;
use Database\Factories\VariationOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'variation_type_id',
    'name',
    'value',
    'description',
    'color_hex',
    'width',
    'height',
    'sort_order',
    'default_modifier_type',
    'default_price_modifier',
])]
/**
 * @property string $name
 * @property VariationType|null $type
 * @property VariationType|null $variationType
 * @property int $id
 * @property int $variation_type_id
 * @property string|null $value
 * @property string|null $color_hex
 * @property float|null $width
 * @property float|null $height
 * @property int $sort_order
 * @property ModifierType|null $default_modifier_type
 * @property numeric $default_price_modifier
 * @property-read int|null $product_variation_options_count
 *
 * @method static \Database\Factories\VariationOptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereColorHex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VariationOption whereVariationTypeId($value)
 *
 * @mixin \Eloquent
 */
class VariationOption extends Model
{
    /**
     * @use HasFactory<VariationOptionFactory>
     */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'default_modifier_type' => ModifierType::class,
    ];

    /**
     * @return BelongsTo<VariationType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    /**
     * @return BelongsTo<VariationType, $this>
     */
    public function variationType(): BelongsTo
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    /**
     * @return HasMany<ProductVariationOption, $this>
     */
    public function productVariationOptions(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class);
    }

    /**
     * Get the SKUs associated with this variation option through the many-to-many relationship.
     * Used by Filament for eager loading in the outlets SKU bulk action form.
     */
    /**
     * @return BelongsToMany<ProductSku, $this>
     */
    public function skus(): BelongsToMany
    {
        return $this->belongsToMany(ProductSku::class, 'product_sku_options', 'variation_option_id', 'product_sku_id')
            ->withPivot(['is_outlet', 'override_price'])
            ->withTimestamps();
    }

    public function getHexColor(): string
    {
        return $this->getHexColors()[0];
    }

    /**
     * Returns an array of 1 or 2 hex codes.
     *
     * Multi-colour variants like "Bianco/Navy" will return two codes so
     * the swatch can be rendered as a diagonal split. Single-colour
     * variants return a one-element array.
     *
     * Resolution order for each colour part:
     *   1. Exact match in $this->color_hex (when the name matches this record)
     *   2. Comprehensive Italian → hex keyword map
     *   3. Fallback grey (#cccccc)
     *
     * @return non-empty-array<string>
     */
    public function getHexColors(): array
    {
        // If there is a stored hex and the name does NOT contain '/', return it directly.
        if ($this->color_hex && ! str_contains($this->name ?? '', '/')) {
            return [$this->color_hex];
        }

        // Multi-colour variant: resolve each component separated by '/'
        if (str_contains($this->name ?? '', '/')) {
            $parts = array_map(trim(...), explode('/', $this->name));
            $hexes = [];

            foreach ($parts as $index => $part) {
                // The first part's hex is already stored in color_hex — use it directly
                $hexes[] = $index === 0 && $this->color_hex ? $this->color_hex : $this->resolveColorName($part);
            }

            // Only treat as multi-colour when we resolved at least 2 distinct colours
            $unique = array_values(array_unique($hexes));
            if (count($unique) >= 2) {
                // Cap at 2 colours for the swatch UI
                return [$unique[0], $unique[1]];
            }

            // Fallback: both parts resolved to the same colour — $unique is non-empty
            return [$unique[0]];
        }

        // No stored hex — derive from name/value
        if ($this->color_hex) {
            return [$this->color_hex];
        }

        if (str_starts_with($this->value ?? '', '#')) {
            return [$this->value];
        }

        return [$this->resolveColorName($this->name ?? '')];
    }

    /**
     * Resolve a plain Italian (or English) colour name to a hex code.
     * Uses longest-match so "Verde Bandiera" beats "Verde".
     */
    private function resolveColorName(string $name): string
    {
        return app(VariationOptionColorService::class)->resolveColorName($name);
    }

    protected function casts(): array
    {
        return [
            'default_modifier_type' => ModifierType::class,
            'default_price_modifier' => 'decimal:2',
        ];
    }
}
