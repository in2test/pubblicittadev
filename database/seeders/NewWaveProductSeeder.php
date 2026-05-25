<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductClass;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewWaveProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example input format:
        // Category Name
        // SKU1 SKU2 SKU3
        $input = <<<'EOD'
T-Shirts & Tops
029030 029031 029029 029035 029368 029033 029034 029364 029365 029320 029360 029361 029348 029349 029358 029359 029340 029341 029356 029342 029343 029344 029352 029353 029390 029391 029006 029317 029319 029318 029305 029005 029307 029367 029351 029411 029460 029038 029039 029040 029041 029338 029339 029345 029346 029334 029335 029376 029377 029326 029314 021174 

Polo
02830 02831 028255 028280 028235 028244 028246 028245 028247 028204 028264 028265 028260 028261 028240 028241 028262 028263 028250 02251 028252 028253 028277 028219 4028219 028272 028273 028237 028242 028243 028274 028222 028223 028254 

Camicie
027962 027960 027961 027311 027321 027950 027955 027347 027310 

Felpe
021030 021031 021038 021039 021032 021033 021034 021035 0201050 0201052 0201054 0201030 0201031 0201034 0201038 0201037 0201033 0201032 021040 021043 021048 021049 021041 021042 021044 021045 021000 021001 021002 021003 021004 021005 021006 021007 021018 021019 021016 021011 021014 021015 021010 021013 021022 021023 021024 021025 021062 021063 021064 021065 021074 021075 021051 021053 021055 

Pile & Softshell
023946 023947 023902 023901 023914 023915 0200911 0200916 0200910 0200915 0200913 0200912 0200917 020958 020959 020954 020957 020952 020953 020980 020981 020927 020928 

Pantaloni
021037 0201056 021008 021009 021017 021066 022057 022053 022064 022054 022065 022045 022045 022040 022041 020902 022046 022047

Giacche
020929 020939 020961 020964 0200964 0200965 020936 020923 0200923 0200924 020956 020969 020903 020901 020955 020989 020942 020943 020940 020941 020933 020934 0200974 0200975 0200976 0200977 020974 020975 020976 020977 020931 020932 020985 020913 020996 020970 020972 020997 

Junior
029032 029362 020937 029347 028232 028233 021028 021020 021021 021027 021072 021068 021067 021069 022055 0201027 0201021 0200909 020096 0200906 020905 0200905 024036 

Borse e Zaini
040163 040161 040162 040165 040164 040103 040207 040208 040235 040236 040220 040223 040224 040222 040221 040301 040302 040303 040304 040312 040311 040313 040315 040314 040242 040241 040243 040246 040250 040247 040248 040244 040245 040249 

Cappellini e sciarpe
024035 024065 024078 024084 024082 024083 024079 024066 024067 024068 024080 024128 024129 024134 024135 024164 024136 024138 024137 024125 024130 024132 024131 024133 

Guanti e accessori
024205 024201 024200 
EOD;

        $parentCategory = Category::query()->where('slug', '=', 'abbigliamento-da-lavoro')->first();

        if (! $parentCategory) {
            $this->command->error('Parent category "Abbigliamento da lavoro" not found!');

            return;
        }

        $service = app(ProductAvailabilityService::class);
        $lines = array_map(trim(...), explode("\n", trim($input)));
        $lines = array_values(array_filter($lines, fn ($line) => $line !== ''));

        for ($i = 0; $i < count($lines); $i += 2) {
            $categoryName = $lines[$i];
            $skusLine = $lines[$i + 1] ?? '';

            if (! $categoryName || ! $skusLine) {
                continue;
            }

            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'parent_id' => $parentCategory->id,
                ]
            );

            $skus = array_filter(preg_split('/[\s,;]+/', $skusLine));

            foreach ($skus as $sku) {
                $sku = trim($sku);
                if (! $sku) {
                    continue;
                }

                /** @var Product|null $product */
                $product = Product::query()->where('sku', '=', $sku)->first();

                if (! $product) {
                    $this->command->info("Product $sku not found locally, fetching from API...");
                    $info = $service->fetchBasicInfo($sku);

                    if ($info) {
                        $product = Product::query()->create([
                            'name' => $info['name'],
                            'sku' => $sku,
                            'slug' => SlugGenerator::unique(Product::class, $info['name']),
                            'type' => Product::TYPE_NEWWAVE,
                            'product_class' => ProductClass::Apparel,
                            'price' => $info['price'] ?? 0,
                            'description' => $info['description'] ?? '',
                            'category_id' => $category->id,
                            'is_active' => true,
                        ]);
                        $this->command->info("Created product $sku: {$product->name}");
                    } else {
                        $this->command->warn("Could not fetch info for SKU $sku from API.");

                        continue;
                    }
                } else {
                    $product->update([
                        'category_id' => $category->id,
                        'type' => Product::TYPE_NEWWAVE,
                        'product_class' => ProductClass::Apparel,
                    ]);
                    $this->command->info("Updated product $sku category to {$category->name}");
                }

                // Dispatch full sync job
                SyncNewWaveProductJob::dispatch($product->id);
            }
        }
    }
}
