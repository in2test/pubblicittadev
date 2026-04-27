<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PricingTier;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_creates_quote_with_valid_data(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 10,
            'customer_name' => 'Mario Rossi',
            'customer_email' => 'mario@example.com',
            'customer_phone' => '123456789',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('quoteSuccess');

        $this->assertDatabaseHas('quotes', [
            'customer_name' => 'Mario Rossi',
            'customer_email' => 'mario@example.com',
            'total_items' => 10,
        ]);
    }

    public function test_uses_pricing_tier_when_available(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        PricingTier::create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'price_per_unit' => 40,
        ]);

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 10,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('quotes', [
            'total_price' => 400.0,
        ]);
    }

    public function test_uses_base_price_when_no_pricing_tier(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('quotes', [
            'total_price' => 250.0,
        ]);
    }

    public function test_creates_quote_item_linked_to_quote(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertStatus(302);

        $quote = Quote::first();
        $this->assertDatabaseHas('quote_items', [
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_calculates_quote_number(): void
    {
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertStatus(302);

        $quote = Quote::first();
        $this->assertStringStartsWith('QT-', $quote->quote_number);
    }

    public function test_validates_required_customer_fields(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['customer_name', 'customer_email']);
    }

    public function test_validates_product_exists(): void
    {
        $response = $this->post(route('quote.store'), [
            'product_id' => 99999,
            'quantity' => 1,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['product_id']);
    }

    public function test_validates_minimum_quantity(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 0,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_validates_email_format(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'customer_name' => 'Test',
            'customer_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['customer_email']);
    }

    public function test_accepts_optional_color_association(): void
    {
        $this->markTestSkipped('Requires product_colors table');
    }

    public function test_accepts_design_file_upload(): void
    {
        $product = Product::factory()->create();

        $file = UploadedFile::fake()->create('design.pdf', 512, 'application/pdf');

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'design_file' => $file,
        ]);

        $response->assertStatus(302);

        $quoteItem = QuoteItem::first();
        $this->assertNotNull($quoteItem->design_file_path);
    }

    public function test_accepts_whatsapp_number(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_whatsapp' => '393123456789',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('quotes', [
            'customer_whatsapp' => '393123456789',
        ]);
    }

    public function test_accepts_notes(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'notes' => 'Please rush delivery.',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('quotes', [
            'notes' => 'Please rush delivery.',
        ]);
    }

    public function test_accepts_customization_points(): void
    {
        $product = Product::factory()->create();

        $response = $this->post(route('quote.store'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customization_points' => ['embroidery', 'custom labels'],
        ]);

        $response->assertStatus(302);
    }
}
