<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SyncLevel;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AdminProductActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_product_active_state(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.products.toggle-active', ['product' => $product->slug]));

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_dispatch_product_sync(): void
    {
        Bus::fake();

        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.products.sync', ['product' => $product->slug]));

        $response->assertRedirect();
        Bus::assertDispatched(SyncNewWaveProductJob::class, fn ($job) => $job->productId === $product->id);
    }

    public function test_admin_can_dispatch_product_sync_at_the_requested_level(): void
    {
        Bus::fake();

        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.products.sync', ['product' => $product->slug]), [
                'level' => SyncLevel::Availability->value,
            ]);

        $response->assertRedirect();
        Bus::assertDispatched(
            SyncNewWaveProductJob::class,
            fn ($job) => $job->productId === $product->id && $job->level === SyncLevel::Availability,
        );
    }

    public function test_admin_sync_rejects_an_unknown_sync_level(): void
    {
        Bus::fake();

        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.products.sync', ['product' => $product->slug]), [
                'level' => 'not-a-level',
            ])
            ->assertSessionHasErrors('level');

        Bus::assertNotDispatched(SyncNewWaveProductJob::class);
    }

    public function test_non_admin_cannot_perform_admin_product_actions(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel', 'description' => null]);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $user = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->post(route('admin.products.toggle-active', ['product' => $product->slug]));

        $response->assertForbidden();
    }
}
