<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModifierType;
use Carbon\CarbonImmutable;
use Database\Factories\VariationOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, ProductVariationOption> $productVariationOptions
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

    protected $casts = [
        'default_modifier_type' => ModifierType::class,
        'default_price_modifier' => 'decimal:2',
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
        $normalized = strtolower(trim($name));

        /**
         * Italian / English colour name → hex map.
         * Keys are lowercase; longer/more-specific names come first so
         * they win over shorter substring matches.
         */
        $colorMap = [
            // Whites & creams
            'bianco avorio' => '#fffeef',
            'bianco perla' => '#eae6ca',
            'bianco' => '#ffffff',
            'white' => '#ffffff',
            'ecru' => '#cdb891',
            // Yellows
            'giallo hv' => '#ffff00',
            'giallo limone' => '#c7b446',
            'giallo neon' => '#eaff00',
            'giallo' => '#ffd700',
            'yellow' => '#ffd700',
            'flumino' => '#eaff00',
            // Oranges
            'arancio bruciato' => '#ff7514',
            'arancio' => '#ff9900',
            'arancione' => '#ffa500',
            // Pinks & reds
            'rosa neon' => '#ff69b4',
            'rosa antico' => '#d36e70',
            'rosa' => '#ffc0cb',
            'pink' => '#ffc0cb',
            'rosso mattone' => '#b22222',
            'rosso melange' => '#cc2200',
            'rosso' => '#ff0000',
            'red' => '#ff0000',
            'bordeaux' => '#800000',
            'burgundy' => '#800020',
            'maroon' => '#800000',
            // Purples
            'viola' => '#8f00ff',
            'purple' => '#8338ec',
            // Blues
            'azzurro brillante' => '#0096ff',
            'azzurro neon' => '#39cfff',
            'azzurro polvere' => '#b0c4de',
            'azzurro pastello' => '#afeeee',
            'azzurro cielo' => '#87ceeb',
            'azzurro melange' => '#007fff',
            'azzurro' => '#007fff',
            'light blue' => '#add8e6',
            'blu cielo' => '#76b5c5',
            'blu elettrico' => '#00a2ff',
            'blu navy' => '#000080',
            'blu melange' => '#0000ff',
            'blu bandiera' => '#0033a0',
            'blu polare' => '#a7c7e7',
            'blu nebbia' => '#778899',
            'blu acciaio' => '#4682b4',
            'blu artico' => '#7fbfe8',
            'blu' => '#0000ff',
            'blue' => '#0000ff',
            'cobalto' => '#0047ab',
            'cobalt' => '#0047ab',
            'royal' => '#4169e1',
            'turchese' => '#30d5c8',
            'turquoise' => '#30d5c8',
            'petrolio' => '#005f6a',
            'denim' => '#1560bd',
            'navy melange' => '#000080',
            'dark navy' => '#00004f',
            'navy' => '#000080',
            // Greens
            'verde bandiera' => '#009246',
            'verde militare' => '#556832',
            'verde foresta' => '#228b22',
            'verde bottiglia' => '#343b29',
            'verde bamboo' => '#40826d',
            'verde bosco' => '#228b22',
            'verde salvia' => '#9dc183',
            'verde melange' => '#66ff00',
            'verde mela' => '#66ff00',
            'verde acqua' => '#7fffd4',
            'verde acido' => '#7fff00',
            'verde lime' => '#ccff00',
            'verde neon' => '#39ff14',
            'verde' => '#008000',
            'green' => '#008000',
            'olive' => '#556b2f',
            'neon' => '#39ff14',
            // Beiges & browns
            'beige melange' => '#d6c7a1',
            'beige' => '#f5f5dc',
            'khaki' => '#c3b091',
            'sabbia' => '#f4a460',
            'sabbia melange' => '#cdb79e',
            'nocciola' => '#b08968',
            'cammello' => '#c19a6b',
            'marrone moka' => '#8a5a3a',
            'marrone' => '#8a5a3a',
            'cognac' => '#9a463d',
            // Greys & blacks
            'grigio cenere' => '#e4e5e0',
            'grigio argento' => '#c0c0c0',
            'grigio melange' => '#b2b2b2',
            'grigio metallo' => '#a8a9ad',
            'grigio fumo' => '#e5e5e5',
            'grigio' => '#808080',
            'grey melange' => '#a9a9a9',
            'grey' => '#808080',
            'antracite melange' => '#4a4a4a',
            'antracite' => '#383e42',
            'pietra' => '#8b8c7a',
            'canna di fucile' => '#2f4f4f',
            'nero melange' => '#2f2f2f',
            'nero' => '#000000',
            'black' => '#000000',
        ];

        // Try exact match first
        if (isset($colorMap[$normalized])) {
            return $colorMap[$normalized];
        }

        // Longest-match substring scan
        $bestLen = 0;
        $bestHex = '#cccccc';
        foreach ($colorMap as $keyword => $hex) {
            if (str_contains($normalized, $keyword) && strlen($keyword) > $bestLen) {
                $bestLen = strlen($keyword);
                $bestHex = $hex;
            }
        }

        return $bestHex;
    }
}
