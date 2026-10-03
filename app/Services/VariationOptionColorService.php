<?php

declare(strict_types=1);

namespace App\Services;

class VariationOptionColorService
{
    /**
     * @var array<string, string>
     */
    private const array COLOR_MAP = [
        'bianco avorio' => '#fffeef',
        'bianco perla' => '#eae6ca',
        'bianco' => '#ffffff',
        'white' => '#ffffff',
        'ecru' => '#cdb891',
        'giallo hv' => '#ffff00',
        'giallo limone' => '#c7b446',
        'giallo neon' => '#eaff00',
        'giallo' => '#ffd700',
        'yellow' => '#ffd700',
        'flumino' => '#eaff00',
        'arancio bruciato' => '#ff7514',
        'arancio' => '#ff9900',
        'arancione' => '#ffa500',
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
        'viola' => '#8f00ff',
        'purple' => '#8338ec',
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

    public function resolveColorName(string $name): string
    {
        $normalized = strtolower(trim($name));

        if (isset(self::COLOR_MAP[$normalized])) {
            return self::COLOR_MAP[$normalized];
        }

        $bestLength = 0;
        $bestHex = '#cccccc';

        foreach (self::COLOR_MAP as $keyword => $hex) {
            if (str_contains($normalized, $keyword) && strlen($keyword) > $bestLength) {
                $bestLength = strlen($keyword);
                $bestHex = $hex;
            }
        }

        return $bestHex;
    }
}
