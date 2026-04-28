<?php

$jsonFile = 'c:\sites\pubblicittadev\full_catalogue.json';
$data = json_decode(file_get_contents($jsonFile), true);

$colors = []; // [code => name]
$sizes = [];  // [code => name]

function extractData($item, &$colors, &$sizes)
{
    if (is_array($item)) {
        if (isset($item['colorCode'])) {
            $name = $item['color']['it'] ?? $item['webColors'] ?? '';
            if (is_array($name)) {
                $name = $name[0] ?? '';
            }
            if ($name) {
                $colors[$item['colorCode']] = $name;
            }
        }

        if (isset($item['name']) && isset($item['size'])) {
            // 'size' is the code (e.g. 4), 'name' is display (e.g. S)
            $sizes[$item['size']] = $item['name'];
        }

        foreach ($item as $key => $value) {
            extractData($value, $colors, $sizes);
        }
    }
}

extractData($data, $colors, $sizes);

ksort($colors);
ksort($sizes);

$existingColors = [
    '00' => ['name' => 'Bianco', 'hex' => '#ffffff'],
    '01' => ['name' => 'Bianco Avorio', 'hex' => '#fffeef'],
    '07' => ['name' => 'Bianco Perla', 'hex' => '#eae6ca'],
    '10' => ['name' => 'Giallo Limone', 'hex' => '#c7b446'],
    '11' => ['name' => 'Giallo Hv', 'hex' => '#ffff00'],
    '18' => ['name' => 'Arancio', 'hex' => '#ff9900'],
    '35' => ['name' => 'Rosso', 'hex' => '#ff0000'],
    '38' => ['name' => 'Bordeaux', 'hex' => '#800000'],
    '44' => ['name' => 'Viola', 'hex' => '#8f00ff'],
    '54' => ['name' => 'Turchese', 'hex' => '#30d5c8'],
    '55' => ['name' => 'Royal', 'hex' => '#4169e1'],
    '56' => ['name' => 'Cobalto', 'hex' => '#0047ab'],
    '57' => ['name' => 'Azzurro', 'hex' => '#007fff'],
    '58' => ['name' => 'Blu Navy', 'hex' => '#000080'],
    '67' => ['name' => 'Verde Mela', 'hex' => '#66ff00'],
    '68' => ['name' => 'Verde Bottiglia', 'hex' => '#343b29'],
    '71' => ['name' => 'Verde Militare', 'hex' => '#556832'],
    '90' => ['name' => 'Grigio', 'hex' => '#808080'],
    '91' => ['name' => 'Pietra', 'hex' => '#8b8c7a'],
    '92' => ['name' => 'Grigio Cenere', 'hex' => '#e4e5e0'],
    '94' => ['name' => 'Grigio Argento', 'hex' => '#c0c0c0'],
    '95' => ['name' => 'Grigio Melange', 'hex' => '#b2b2b2'],
    '96' => ['name' => 'Canna Di Fucile', 'hex' => '#2f4f4f'],
    '99' => ['name' => 'Nero', 'hex' => '#000000'],
    '170' => ['name' => 'Arancio Hv', 'hex' => '#ff2301'],
    '175' => ['name' => 'Arancione', 'hex' => '#ffa500'],
    '215' => ['name' => 'Rosa Confetto', 'hex' => '#fadadd'],
    '240' => ['name' => 'Rosa Active', 'hex' => '#fc0fc0'],
    '250' => ['name' => 'Rosa Brillante', 'hex' => '#ff007f'],
    '300' => ['name' => 'Lampone', 'hex' => '#e30b5c'],
    '565' => ['name' => 'Blu Melange', 'hex' => '#5f9ea0'],
    '570' => ['name' => 'Azzurro Pastello', 'hex' => '#afeeee'],
    '580' => ['name' => 'Blu', 'hex' => '#00008b'],
    '595' => ['name' => 'Blu Acciaio', 'hex' => '#4682b4'],
    '600' => ['name' => 'Verde Lime', 'hex' => '#ccff00'],
    '602' => ['name' => 'Verde Active', 'hex' => '#00ff00'],
    '605' => ['name' => 'Verde Acido', 'hex' => '#7fff00'],
    '615' => ['name' => 'Verde Salvia', 'hex' => '#9dc183'],
    '815' => ['name' => 'Beige', 'hex' => '#f5f5dc'],
    '820' => ['name' => 'Caffe Latte', 'hex' => '#d2691e'],
    '825' => ['name' => 'Marrone Moka', 'hex' => '#8a5a3a'],
    '925' => ['name' => 'Nature Melange', 'hex' => '#b5b8b1'],
    '955' => ['name' => 'Antracite Melange', 'hex' => '#293133'],
    '956' => ['name' => 'Grigio Metallo', 'hex' => '#a8a9ad'],
    '04' => ['name' => 'Khaki', 'hex' => '#c3b091'],
    '19' => ['name' => 'Arancio Bruciato', 'hex' => '#ff7514'],
    '203' => ['name' => 'Rosa Antico', 'hex' => '#d36e70'],
    '554' => ['name' => 'Navy Mélange', 'hex' => '#000080'],
    '575' => ['name' => 'Blu Nebbia', 'hex' => '#778899'],
    '581' => ['name' => 'Denim', 'hex' => '#1560bd'],
    '62' => ['name' => 'Verde Bandiera', 'hex' => '#228b22'],
    '66' => ['name' => 'Verde Foresta', 'hex' => '#228b22'],
    '75' => ['name' => 'Verde Bamboo', 'hex' => '#40826d'],
    '82' => ['name' => 'Sabbia', 'hex' => '#f4a460'],
    '945' => ['name' => 'Grigio Fumo', 'hex' => '#e5e5e5'],
    '947' => ['name' => 'Rifrangente', 'hex' => '#f4f4f4'],
    '949' => ['name' => 'Rifrangente Chiaro', 'hex' => '#f6f6f6'],
];

$seeder = "<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\PrintPlacement;
use App\Models\PrintSide;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(['email' => 'deepinart@gmail.com'], [
            'name' => 'Admin User',
            'password' => bcrypt('adelante'),
        ]);

        // Seed Print Placements
        PrintPlacement::updateOrCreate(['name' => 'Fronte'], ['description' => 'Stampa sul fronte 23x30 cm', 'sort_order' => 1, 'default_price' => 3]);
        PrintPlacement::updateOrCreate(['name' => 'Dietro'], ['description' => 'Stampa sul dietro 23x30 cm', 'sort_order' => 2, 'default_price' => 3]);
        PrintPlacement::updateOrCreate(['name' => 'Manica Sinistra'], ['description' => 'Stampa sulla manica sinistra 9x9 cm', 'sort_order' => 3, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Manica Destra'], ['description' => 'Stampa sulla manica destra 9x9 cm', 'sort_order' => 4, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Lato Cuore'], ['description' => 'Stampa sul lato cuore 9x9 cm', 'sort_order' => 5, 'default_price' => 1.80]);
        PrintPlacement::updateOrCreate(['name' => 'Tasca'], ['description' => 'Stampa sulla tasca 9x9 cm', 'sort_order' => 6, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Gamba sinistra'], ['description' => 'Stampa sulla gamba sinistra 9x9 cm', 'sort_order' => 7, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Gamba destra'], ['description' => 'Stampa sulla gamba destra 9x9 cm', 'sort_order' => 8, 'default_price' => 2]);

        // Seed Print Sides
        PrintSide::updateOrCreate(['name' => 'Stampa sul fronte'], ['description' => 'Stampa solo sul fronte', 'sort_order' => 1]);
        PrintSide::updateOrCreate(['name' => 'Fronte e retro uguali'], ['description' => 'Stampa fronte e retro uguali', 'sort_order' => 2]);
        PrintSide::updateOrCreate(['name' => 'Fronte e retro differenti'], ['description' => 'Stampa fronte e retro differenti', 'sort_order' => 3]);

        // Categories
        Category::updateOrCreate(['slug' => 'abbigliamento-da-lavoro'], ['name' => 'Abbigliamento da lavoro', 'parent_id' => null]);
        Category::updateOrCreate(['slug' => 't-shirt'], ['name' => 'T-Shirt', 'parent_id' => 1]);
        Category::updateOrCreate(['slug' => 'polo'], ['name' => 'Polo', 'parent_id' => 1]);
        Category::updateOrCreate(['slug' => 'felpe'], ['name' => 'Felpe', 'parent_id' => 1]);
        Category::updateOrCreate(['slug' => 'pantaloni'], ['name' => 'Pantaloni', 'parent_id' => 1]);
        Category::updateOrCreate(['slug' => 'giacche'], ['name' => 'Giacche', 'parent_id' => 1]);
        Category::updateOrCreate(['slug' => 'gilet'], ['name' => 'Gilet', 'parent_id' => 1]);
        Category::updateOrCreate(['slug' => 'calzature'], ['name' => 'Calzature', 'parent_id' => 1]);

        // Seed Sizes\n";

$sort = 1;
foreach ($sizes as $code => $name) {
    if (! $name || strtolower($name) == 'no size' || strtolower($name) == 'one size') {
        continue;
    }
    $name = str_replace("'", "\'", $name);
    $seeder .= "        Size::updateOrCreate(['size' => '$name', 'size_code' => '$code'], ['size_name' => '$name', 'sort_order' => ".($sort++)."]);\n";
}

$seeder .= "\n        // Seed Colors\n";
$sort = 1;
foreach ($colors as $code => $name) {
    if (isset($existingColors[$code])) {
        $colorName = str_replace("'", "\'", $existingColors[$code]['name']);
        $hex = $existingColors[$code]['hex'];
    } else {
        $colorName = str_replace("'", "\'", mb_convert_case($name, MB_CASE_TITLE, 'UTF-8'));
        $hex = '#CCCCCC';
    }
    $seeder .= "        Color::updateOrCreate(['color_code' => '$code'], ['color_name' => '$colorName', 'color_hex' => '$hex', 'sort_order' => ".($sort++)."]);\n";
}

$seeder .= "    }\n}\n";

file_put_contents('c:\sites\pubblicittadev\database\seeders\DatabaseSeeder.php', $seeder);
echo "DatabaseSeeder.php recreated with size_code support.\n";
