<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewWaveProductFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_by_category_works_on_newwave_table_query(): void
    {
        // Create categories
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $catA = Category::create(['name' => 'Alpha', 'slug' => 'alpha', 'parent_id' => $root->id]);
        $catB = Category::create(['name' => 'Beta', 'slug' => 'beta', 'parent_id' => $root->id]);

        // Create two NewWave products in different categories
        $nwA = Product::create([
            'name' => 'NW A',
            'sku' => 'NWA',
            'slug' => 'nw-a',
            'price' => 10,
            'category_id' => $catA->id,
            'type' => Product::TYPE_NEWWAVE,
            'description' => 'NW A',
            'is_featured' => false,
            'override_price' => false,
            'sync_status' => SyncStatus::Synced,
            'synced_at' => now(),
            'is_active' => true,
        ]);

        $nwB = Product::create([
            'name' => 'NW B',
            'sku' => 'NWB',
            'slug' => 'nw-b',
            'price' => 20,
            'category_id' => $catB->id,
            'type' => Product::TYPE_NEWWAVE,
            'description' => 'NW B',
            'is_featured' => false,
            'override_price' => false,
            'sync_status' => SyncStatus::Synced,
            'synced_at' => now(),
            'is_active' => true,
        ]);

        // Get base query for NewWave products (filters by type)
        $baseQuery = NewWaveProductResource::getEloquentQuery();

        // Filter by Alpha category
        $qA = clone $baseQuery;
        $resultsA = $qA->where('category_id', $catA->id)->get();
        $this->assertCount(1, $resultsA);
        $this->assertEquals($nwA->id, $resultsA->first()->id);

        // Filter by Beta category
        $qB = clone $baseQuery;
        $resultsB = $qB->where('category_id', $catB->id)->get();
        $this->assertCount(1, $resultsB);
        $this->assertEquals($nwB->id, $resultsB->first()->id);
    }
}
