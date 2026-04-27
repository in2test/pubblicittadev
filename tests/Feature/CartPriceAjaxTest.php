<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPriceAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_price_requires_seeded_data(): void
    {
        $this->markTestSkipped('Requires print_placements pivot table and seeded data');
    }
}
