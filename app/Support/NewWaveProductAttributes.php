<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;

class NewWaveProductAttributes
{
    /**
     * Map of care instruction keys to Italian descriptions, short labels, and icons.
     *
     * @var array<string, array{label: string, short: string, icon: string, symbol: string, category: string}>
     */
    public const CARE_INSTRUCTIONS_MAP = [
        'MachineWash30' => [
            'label' => 'Lavaggio in lavatrice a 30°C',
            'short' => 'Lavaggio 30°C',
            'icon' => 'local_laundry_service',
            'symbol' => '30°',
            'category' => 'wash',
        ],
        'MachineWash40' => [
            'label' => 'Lavaggio in lavatrice a 40°C',
            'short' => 'Lavaggio 40°C',
            'icon' => 'local_laundry_service',
            'symbol' => '40°',
            'category' => 'wash',
        ],
        'MachineWash60' => [
            'label' => 'Lavaggio in lavatrice a 60°C',
            'short' => 'Lavaggio 60°C',
            'icon' => 'local_laundry_service',
            'symbol' => '60°',
            'category' => 'wash',
        ],
        'MachineWash95' => [
            'label' => 'Lavaggio in lavatrice a 95°C',
            'short' => 'Lavaggio 95°C',
            'icon' => 'local_laundry_service',
            'symbol' => '95°',
            'category' => 'wash',
        ],
        'MachineWashGentle30' => [
            'label' => 'Lavaggio delicato in lavatrice a 30°C',
            'short' => 'Lavaggio delicato 30°C',
            'icon' => 'local_laundry_service',
            'symbol' => '30°_',
            'category' => 'wash',
        ],
        'MachineWashGentle40' => [
            'label' => 'Lavaggio delicato in lavatrice a 40°C',
            'short' => 'Lavaggio delicato 40°C',
            'icon' => 'local_laundry_service',
            'symbol' => '40°_',
            'category' => 'wash',
        ],
        'HandWash' => [
            'label' => 'Lavaggio esclusivamente a mano',
            'short' => 'Lavaggio a mano',
            'icon' => 'wash',
            'symbol' => 'hand',
            'category' => 'wash',
        ],
        'DoNotWash' => [
            'label' => 'Non lavare con acqua',
            'short' => 'Non lavare',
            'icon' => 'block',
            'symbol' => 'no_wash',
            'category' => 'wash',
        ],
        'DoNotBleach' => [
            'label' => 'Non trattare con cloro / Non candeggiare',
            'short' => 'Non candeggiare',
            'icon' => 'change_history',
            'symbol' => 'no_bleach',
            'category' => 'bleach',
        ],
        'BleachAllowed' => [
            'label' => 'Candeggio consentito con qualsiasi agente',
            'short' => 'Candeggio consentito',
            'icon' => 'change_history',
            'symbol' => 'bleach',
            'category' => 'bleach',
        ],
        'IroningLowTemp' => [
            'label' => 'Stirare a bassa temperatura (max 110°C, senza vapore)',
            'short' => 'Stiro max 110°C',
            'icon' => 'iron',
            'symbol' => 'iron_1',
            'category' => 'iron',
        ],
        'IroningMediumTemp' => [
            'label' => 'Stirare a media temperatura (max 150°C)',
            'short' => 'Stiro max 150°C',
            'icon' => 'iron',
            'symbol' => 'iron_2',
            'category' => 'iron',
        ],
        'IroningHighTemp' => [
            'label' => 'Stirare ad alta temperatura (max 200°C)',
            'short' => 'Stiro max 200°C',
            'icon' => 'iron',
            'symbol' => 'iron_3',
            'category' => 'iron',
        ],
        'DoNotIron' => [
            'label' => 'Non stirare / Trattamento senza stiratura',
            'short' => 'Non stirare',
            'icon' => 'block',
            'symbol' => 'no_iron',
            'category' => 'iron',
        ],
        'DoNotTumble' => [
            'label' => 'Non asciugare a mezzo di asciugatrice',
            'short' => 'No asciugatrice',
            'icon' => 'dry',
            'symbol' => 'no_tumble',
            'category' => 'dry',
        ],
        'Tumble' => [
            'label' => 'Asciugatura in asciugatrice consentita (temperatura normale)',
            'short' => 'Asciugatrice consentita',
            'icon' => 'dry',
            'symbol' => 'tumble',
            'category' => 'dry',
        ],
        'TumbleLowTemp' => [
            'label' => 'Asciugatura a tamburo a temperatura ridotta',
            'short' => 'Asciugatrice delicata',
            'icon' => 'dry',
            'symbol' => 'tumble_low',
            'category' => 'dry',
        ],
        'DoNotDryClean' => [
            'label' => 'Non lavare a secco',
            'short' => 'Non lavare a secco',
            'icon' => 'cancel',
            'symbol' => 'no_dry_clean',
            'category' => 'clean',
        ],
        'DryCleanP' => [
            'label' => 'Lavaggio a secco professionale con percloroetilene (ciclo normale)',
            'short' => 'Lavaggio a secco (P)',
            'icon' => 'dry_cleaning',
            'symbol' => 'dry_clean_p',
            'category' => 'clean',
        ],
        'DryCleanF' => [
            'label' => 'Lavaggio a secco professionale con idrocarburi (ciclo delicato)',
            'short' => 'Lavaggio a secco (F)',
            'icon' => 'dry_cleaning',
            'symbol' => 'dry_clean_f',
            'category' => 'clean',
        ],
    ];

    /**
     * Map of feature keys to Italian descriptions, short labels, and icons.
     *
     * @var array<string, array{label: string, short: string, icon: string}>
     */
    public const FEATURES_MAP = [
        'Easycut' => [
            'label' => 'Etichetta strappabile Tear-away (Easy cut)',
            'short' => 'Etichetta strappabile',
            'icon' => 'content_cut',
        ],
        'BeActive' => [
            'label' => 'Tessuto tecnico traspirante per attività sportiva (Be Active)',
            'short' => 'Tessuto sportivo',
            'icon' => 'directions_run',
        ],
        'MoistureTransport' => [
            'label' => 'Tecnologia traspirante ad alta espulsione dell\'umidità',
            'short' => 'Traspirante',
            'icon' => 'air',
        ],
        'QuickDry' => [
            'label' => 'Tessuto ad asciugatura rapida (Quick Dry)',
            'short' => 'Asciugatura rapida',
            'icon' => 'bolt',
        ],
    ];

    /**
     * Map of certification keys to Italian descriptions, short labels, and icons.
     *
     * @var array<string, array{label: string, short: string, icon: string}>
     */
    public const CERTIFICATIONS_MAP = [
        'OekoTex' => [
            'label' => 'Oeko-Tex Standard 100 (Assenza di sostanze nocive)',
            'short' => 'Oeko-Tex Standard 100',
            'icon' => 'verified',
        ],
        'UV' => [
            'label' => 'Protezione dai raggi solari UV',
            'short' => 'Protezione UV',
            'icon' => 'wb_sunny',
        ],
        'OekoTexSE14218' => [
            'label' => 'Certificazione Oeko-Tex RISE (SE 14-218)',
            'short' => 'Certificazione RISE',
            'icon' => 'verified',
        ],
    ];

    /**
     * Normalize and format fabric composition text.
     */
    public static function formatFabrics(?string $fabrics): ?string
    {
        if ($fabrics === null) {
            return null;
        }

        $cleaned = trim(preg_replace('/\s+/', ' ', $fabrics) ?? '');

        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Format features into a list of Italian descriptions.
     *
     * @param  array<int, array{key?: string|null, value?: string|null}>  $features
     * @return array<int, string>
     */
    public static function formatFeatures(array $features): array
    {
        return self::formatAttributeList($features, self::FEATURES_MAP);
    }

    /**
     * Format certifications into a list of Italian descriptions.
     *
     * @param  array<int, array{key?: string|null, value?: string|null}>  $certifications
     * @return array<int, string>
     */
    public static function formatCertifications(array $certifications): array
    {
        return self::formatAttributeList($certifications, self::CERTIFICATIONS_MAP);
    }

    /**
     * Format care instructions into a list of Italian descriptions.
     *
     * @param  array<int, array{key?: string|null, value?: string|null}>  $careInstructions
     * @return array<int, string>
     */
    public static function formatCareInstructions(array $careInstructions): array
    {
        return self::formatAttributeList($careInstructions, self::CARE_INSTRUCTIONS_MAP);
    }

    /**
     * @param  array<int, array{key?: string|null, value?: string|null}>  $items
     * @param  array<string, array{label: string}>  $map
     * @return array<int, string>
     */
    private static function formatAttributeList(array $items, array $map): array
    {
        $result = [];

        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            $val = $item['value'] ?? null;

            if (isset($map[$key])) {
                $result[] = $map[$key]['label'];
            } elseif (! empty($val)) {
                $result[] = (string) $val;
            } elseif ($key !== '') {
                $result[] = $key;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Parse stored care instructions text or array and return structured detail items for UI rendering.
     *
     * @param  string|array<int, mixed>|null  $input
     * @return array<int, array{key: string, label: string, short: string, icon: string, symbol: string, category: string}>
     */
    public static function parseCareDetails(string|array|null $input): array
    {
        if (empty($input)) {
            return [];
        }

        $items = is_array($input) ? $input : array_map(trim(...), explode(',', $input));
        $details = [];

        foreach ($items as $item) {
            $str = is_array($item) ? ($item['key'] ?? $item['value'] ?? '') : (string) $item;
            $str = trim($str);
            if ($str === '') {
                continue;
            }

            // Direct key match
            if (isset(self::CARE_INSTRUCTIONS_MAP[$str])) {
                $data = self::CARE_INSTRUCTIONS_MAP[$str];
                $details[] = [
                    'key' => $str,
                    'label' => $data['label'],
                    'short' => $data['short'],
                    'icon' => $data['icon'],
                    'symbol' => $data['symbol'],
                    'category' => $data['category'],
                ];

                continue;
            }

            // Reverse match from Italian description
            $matchedKey = null;
            foreach (self::CARE_INSTRUCTIONS_MAP as $key => $info) {
                if (stripos($str, $info['short']) !== false || stripos($str, $info['label']) !== false) {
                    $matchedKey = $key;
                    break;
                }
            }

            if ($matchedKey !== null) {
                $data = self::CARE_INSTRUCTIONS_MAP[$matchedKey];
                $details[] = [
                    'key' => $matchedKey,
                    'label' => $data['label'],
                    'short' => $data['short'],
                    'icon' => $data['icon'],
                    'symbol' => $data['symbol'],
                    'category' => $data['category'],
                ];
            } else {
                $details[] = [
                    'key' => 'custom',
                    'label' => $str,
                    'short' => $str,
                    'icon' => 'local_laundry_service',
                    'symbol' => 'wash',
                    'category' => 'other',
                ];
            }
        }

        return $details;
    }

    /**
     * Parse stored features text and return structured items with icons.
     *
     * @param  string|array<int, mixed>|null  $input
     * @return array<int, array{key: string, label: string, short: string, icon: string}>
     */
    public static function parseFeatureDetails(string|array|null $input): array
    {
        return self::parseSimpleMappedDetails($input, self::FEATURES_MAP, 'check_circle');
    }

    /**
     * Parse stored certifications text and return structured items with icons.
     *
     * @param  string|array<int, mixed>|null  $input
     * @return array<int, array{key: string, label: string, short: string, icon: string}>
     */
    public static function parseCertificationDetails(string|array|null $input): array
    {
        return self::parseSimpleMappedDetails($input, self::CERTIFICATIONS_MAP, 'verified');
    }

    /**
     * @param  string|array<int, mixed>|null  $input
     * @param  array<string, array{label: string, short: string, icon: string}>  $map
     * @return array<int, array{key: string, label: string, short: string, icon: string}>
     */
    private static function parseSimpleMappedDetails(string|array|null $input, array $map, string $defaultIcon): array
    {
        if (empty($input)) {
            return [];
        }

        $items = is_array($input) ? $input : array_map(trim(...), explode(',', $input));
        $details = [];

        foreach ($items as $item) {
            $str = is_array($item) ? ($item['key'] ?? $item['value'] ?? '') : (string) $item;
            $str = trim($str);
            if ($str === '') {
                continue;
            }

            if (isset($map[$str])) {
                $data = $map[$str];
                $details[] = [
                    'key' => $str,
                    'label' => $data['label'],
                    'short' => $data['short'],
                    'icon' => $data['icon'],
                ];

                continue;
            }

            $matchedKey = null;
            foreach ($map as $key => $info) {
                if (stripos($str, $info['short']) !== false || stripos($str, $key) !== false) {
                    $matchedKey = $key;
                    break;
                }
            }

            if ($matchedKey !== null) {
                $data = $map[$matchedKey];
                $details[] = [
                    'key' => $matchedKey,
                    'label' => $data['label'],
                    'short' => $data['short'],
                    'icon' => $data['icon'],
                ];
            } else {
                $details[] = [
                    'key' => 'custom',
                    'label' => $str,
                    'short' => $str,
                    'icon' => $defaultIcon,
                ];
            }
        }

        return $details;
    }



    /**
     * Check if customization_notes looks like care instructions.
     */
    public static function isCareInstructionText(?string $text, ?string $productType = null): bool
    {
        if ($text === null || $text === '') {
            return false;
        }

        if ($productType === Product::TYPE_NEWWAVE) {
            return true;
        }

        return (bool) preg_match('/(lavaggio|lavare|stirare|stiro|asciugatrice|candegg|secco|wash|iron|tumble|bleach)/i', $text);
    }
}
