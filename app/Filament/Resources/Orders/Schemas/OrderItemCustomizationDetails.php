<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class OrderItemCustomizationDetails
{
    public static function field(): Placeholder
    {
        return Placeholder::make('customization_details')
            ->label('Dettagli Lavorazione / Note')
            ->content(fn ($record): string|HtmlString => self::render($record))
            ->columnSpanFull();
    }

    private static function render(mixed $record): string|HtmlString
    {
        if (! $record || ! $record->customization_json) {
            return '-';
        }

        $json = $record->customization_json;
        $html = '<ul class="list-disc pl-4 space-y-1 text-sm">';
        $quantity = $record->quantity ?? 1;
        $thicknessMm = self::appendOptionsSummary($json['options_summary'] ?? [], $html);
        $width = isset($json['width']) ? (float) $json['width'] : 0;
        $height = isset($json['height']) ? (float) $json['height'] : 0;
        $product = $record->relationLoaded('product') ? $record->product : Product::find($record->product_id);

        self::appendDimensionDetails($html, $width, $height, $quantity, $thicknessMm, $product);
        self::appendQuantityBreakdown($json, $html, $product);
        self::appendItemNotes($json, $html);

        return new HtmlString($html.'</ul>');
    }

    /**
     * @param  array<string, mixed>  $optionsSummary
     */
    private static function appendOptionsSummary(array $optionsSummary, string &$html): float
    {
        $thicknessMm = 0;

        foreach ($optionsSummary as $key => $value) {
            $html .= "<li><strong>{$key}:</strong> {$value}</li>";
            $combined = strtolower($key.' '.$value);

            if ((str_contains($combined, 'spessore') || str_contains($combined, 'profondit') || str_contains($combined, 'telaio')) && preg_match('/([0-9.,]+)\s*(mm|cm)/i', $combined, $matches)) {
                $thicknessMm = (float) str_replace(',', '.', $matches[1]);
                if (strtolower($matches[2]) === 'cm') {
                    $thicknessMm *= 10;
                }
            }
        }

        return $thicknessMm;
    }

    private static function appendDimensionDetails(string &$html, float $width, float $height, mixed $quantity, float $thicknessMm, ?Product $product): void
    {
        if ($width <= 0 || $height <= 0) {
            return;
        }

        $html .= "<li><strong>Dimensioni singole:</strong> {$width} x {$height} mm</li>";
        $totalSqm = ($width * $height) / 1000000.0 * $quantity;
        $html .= '<li><strong>Area Totale:</strong> '.number_format($totalSqm, 2, ',', '.').' mq</li>';

        if ($product instanceof Product && $product->sheet_width > 0 && $product->sheet_height > 0) {
            $sheetWidth = (float) $product->sheet_width;
            $sheetHeight = (float) $product->sheet_height;
            $fit1 = floor($sheetWidth / $width) * floor($sheetHeight / $height);
            $fit2 = floor($sheetWidth / $height) * floor($sheetHeight / $width);
            $itemsPerSheet = max($fit1, $fit2);

            if ($itemsPerSheet > 0) {
                $sheetsNeeded = ceil($quantity / $itemsPerSheet);
                $html .= "<li><strong>Impaginazione:</strong> {$itemsPerSheet} pz per lastra ({$sheetWidth}x{$sheetHeight}mm). Totale lastre necessarie: {$sheetsNeeded}</li>";
            }
        }

        if ($thicknessMm > 0) {
            $packDepth = $thicknessMm * $quantity;
            $html .= "<li><strong>Ingombro Pacco Stimato:</strong> {$width} x {$height} x {$packDepth} mm</li>";
        }
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private static function appendQuantityBreakdown(array $json, string &$html, ?Product $product): void
    {
        $quantities = $json['quantities'] ?? [];
        $showQuantities = true;
        /* @phpstan-ignore-next-line Filament scaffold: ProductClass constant not yet defined */
        if (! empty($quantities) && count($quantities) === 1 && ($product instanceof Product && $product->product_class === ProductClass::AreaBased)) {
            $showQuantities = false;
        }

        if (empty($quantities) || ! $showQuantities) {
            return;
        }

        $html .= '<li><strong>Taglie/Varianti:</strong> ';
        $html .= '<ul class="pl-4 mt-1 space-y-1">';
        $skus = ProductSku::query()
            ->with('options.type')
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        foreach ($quantities as $skuId => $quantity) {
            /** @var ProductSku|null $sku */
            $sku = $skus->get((int) $skuId);
            if ($sku && $sku->options->isNotEmpty()) {
                $optionLabels = $sku->options->map(fn (VariationOption $option) => ($option->type ? ($option->type->getAttribute('name')).': ' : '').($option->getAttribute('name')))->join(', ');
                $skuLabel = $optionLabels;
            } else {
                $skuLabel = "Variante #{$skuId}";
            }

            $html .= "<li>- {$skuLabel}: <strong>{$quantity} pz</strong></li>";
        }

        $html .= '</ul></li>';
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private static function appendItemNotes(array $json, string &$html): void
    {
        if (! empty($json['notes'])) {
            $html .= "<li><strong>Note Specifica Articolo:</strong> <span class=\"text-red-600 font-bold\">{$json['notes']}</span></li>";
        }
    }
}
