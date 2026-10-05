<?php

declare(strict_types=1);

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\RelationManagers\ProductsRelationManager;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\ProductPricingService;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

it('activates campaigns only within their start and end date-times', function (): void {
    $now = CarbonImmutable::now();

    $activeCampaign = Campaign::factory()->create([
        'starts_at' => $now->subMinute(),
        'ends_at' => $now->addMinute(),
    ]);
    $futureCampaign = Campaign::factory()->create([
        'starts_at' => $now->addDay(),
        'ends_at' => $now->addDays(2),
    ]);
    $endedCampaign = Campaign::factory()->create([
        'starts_at' => $now->subDays(2),
        'ends_at' => $now->subDay(),
    ]);

    expect(Campaign::active()->pluck('id')->all())
        ->toBe([$activeCampaign->id])
        ->and($activeCampaign->isActive())->toBeTrue()
        ->and($futureCampaign->isActive())->toBeFalse()
        ->and($endedCampaign->isActive())->toBeFalse();
});

it('creates a campaign with unassigned offers and outlet variants', function (): void {
    $offer = Product::factory()->create([
        'offer_price' => 19.99,
        'is_active' => true,
    ]);
    $outletProduct = Product::factory()->create();
    $outletSku = ProductSku::factory()->for($outletProduct)->create([
        'is_outlet' => true,
        'override_price' => 9.99,
    ]);
    $startsAt = CarbonImmutable::now()->addDay();
    $endsAt = $startsAt->addWeek();

    Livewire::test(CreateCampaign::class)
        ->fillForm([
            'name' => 'Winter Sale',
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'products' => [$offer->id],
            'outletSkus' => [$outletSku->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campaign = Campaign::query()->where('name', 'Winter Sale')->firstOrFail();

    expect($campaign->products->modelKeys())->toBe([$offer->id])
        ->and($campaign->outletSkus->modelKeys())->toBe([$outletSku->id])
        ->and((bool) $offer->fresh()->is_active)->toBe((bool) $offer->is_active)
        ->and((bool) $outletSku->fresh()->is_outlet)->toBeTrue();
});

it('applies offer and outlet prices only while an assigned campaign is active', function (): void {
    $product = Product::factory()->create([
        'price' => 100,
        'offer_price' => 60,
    ]);
    $sku = ProductSku::factory()->for($product)->create([
        'is_outlet' => true,
        'override_price' => 40,
    ]);
    $pricing = app(ProductPricingService::class);

    expect($pricing->getPriceForQuantity($product, 1))->toBe(100.0)
        ->and($pricing->getSkuPriceForQuantity($product, 1, $sku))->toBe(100.0);

    $campaign = Campaign::factory()->create();
    $campaign->products()->attach($product);
    $campaign->outletSkus()->attach($sku);

    expect($pricing->getPriceForQuantity($product, 1))->toBe(60.0)
        ->and($pricing->getSkuPriceForQuantity($product, 1, $sku))->toBe(40.0);

    $campaign->update(['ends_at' => CarbonImmutable::now()->subMinute()]);

    expect($pricing->getPriceForQuantity($product, 1))->toBe(100.0)
        ->and($pricing->getSkuPriceForQuantity($product, 1, $sku))->toBe(100.0);
});

it('displays campaigns in the Filament list', function (): void {
    $campaign = Campaign::factory()->create(['name' => 'Spring Offers']);

    Livewire::test(ListCampaigns::class)
        ->assertCanSeeTableRecords([$campaign])
        ->assertSee('Spring Offers');
});

it('keeps the campaign current offer and outlet associations while editing', function (): void {
    $offer = Product::factory()->create(['offer_price' => 19.99]);
    $outletProduct = Product::factory()->create();
    $outletSku = ProductSku::factory()->for($outletProduct)->create(['is_outlet' => true]);
    $campaign = Campaign::factory()->create();
    $campaign->products()->attach($offer);
    $campaign->outletSkus()->attach($outletSku);

    Livewire::test(EditCampaign::class, ['record' => $campaign->getKey()])
        ->fillForm(['name' => 'Updated Campaign'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($campaign->fresh()->name)->toBe('Updated Campaign')
        ->and($campaign->fresh()->products->modelKeys())->toBe([$offer->id])
        ->and($campaign->fresh()->outletSkus->modelKeys())->toBe([$outletSku->id]);
});

it('offers only unassigned products and outlet variants in the creation form', function (): void {
    $availableOffer = Product::factory()->create([
        'name' => 'Available Offer',
        'offer_price' => 19.99,
    ]);
    $assignedOffer = Product::factory()->create([
        'name' => 'Assigned Offer',
        'offer_price' => 9.99,
    ]);
    $availableSku = ProductSku::factory()->for(Product::factory()->create(['name' => 'Available Outlet']))
        ->create(['sku' => 'AVAILABLE-SKU', 'is_outlet' => true]);
    $assignedSku = ProductSku::factory()->for(Product::factory()->create(['name' => 'Assigned Outlet']))
        ->create(['sku' => 'ASSIGNED-SKU', 'is_outlet' => true]);
    $existingCampaign = Campaign::factory()->create();
    $existingCampaign->products()->attach($assignedOffer);
    $existingCampaign->outletSkus()->attach($assignedSku);

    Livewire::test(CreateCampaign::class)
        ->assertFormFieldExists(
            'products',
            fn (Select $field): bool => $field->getSearchResultsFromRelationship('Offer')
                === [$availableOffer->getKey() => 'Available Offer'],
        )
        ->assertFormFieldExists(
            'outletSkus',
            fn (Select $field): bool => $field->getSearchResultsFromRelationship('SKU')
                === [$availableSku->getKey() => 'Available Outlet — AVAILABLE-SKU'],
        );
});

it('lists all products that have an offer or outlet variant in the relation manager', function (): void {
    $campaign = Campaign::factory()->create();

    $offerProduct = Product::factory()->create([
        'name' => 'Offer Only Product',
        'offer_price' => 25.00,
    ]);

    $outletProduct = Product::factory()->create([
        'name' => 'Outlet Only Product',
        'offer_price' => null,
    ]);
    ProductSku::factory()->for($outletProduct)->create([
        'is_outlet' => true,
        'override_price' => 15.00,
    ]);

    $regularProduct = Product::factory()->create([
        'name' => 'Regular Product',
        'offer_price' => null,
    ]);
    ProductSku::factory()->for($regularProduct)->create([
        'is_outlet' => false,
    ]);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $campaign,
        'pageClass' => EditCampaign::class,
    ])
        ->assertCanSeeTableRecords([$offerProduct, $outletProduct])
        ->assertCanNotSeeTableRecords([$regularProduct]);
});

it('associates and detaches product offer and all outlet skus with the campaign', function (): void {
    $campaign = Campaign::factory()->create();

    $product = Product::factory()->create([
        'name' => 'Promo Combo Product',
        'offer_price' => 30.00,
    ]);
    $outletSku1 = ProductSku::factory()->for($product)->create([
        'sku' => 'COMBO-OUTLET-1',
        'is_outlet' => true,
        'override_price' => 20.00,
    ]);
    $outletSku2 = ProductSku::factory()->for($product)->create([
        'sku' => 'COMBO-OUTLET-2',
        'is_outlet' => true,
        'override_price' => 22.00,
    ]);
    $standardSku = ProductSku::factory()->for($product)->create([
        'sku' => 'COMBO-STANDARD',
        'is_outlet' => false,
    ]);

    $component = Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $campaign,
        'pageClass' => EditCampaign::class,
    ]);

    // Associate product
    $component->callTableAction('associate', $product);

    $campaign->refresh();
    expect($campaign->products->modelKeys())->toContain($product->id)
        ->and($campaign->outletSkus->modelKeys())->toContain($outletSku1->id)
        ->and($campaign->outletSkus->modelKeys())->toContain($outletSku2->id)
        ->and($campaign->outletSkus->modelKeys())->not->toContain($standardSku->id);

    // Detach product
    $component->callTableAction('detach', $product);

    $campaign->refresh();
    expect($campaign->products->modelKeys())->not->toContain($product->id)
        ->and($campaign->outletSkus->modelKeys())->not->toContain($outletSku1->id)
        ->and($campaign->outletSkus->modelKeys())->not->toContain($outletSku2->id);
});

it('bulk associates and bulk detaches selected products with the campaign', function (): void {
    $campaign = Campaign::factory()->create();

    $productA = Product::factory()->create(['offer_price' => 20.00]);
    $skuA = ProductSku::factory()->for($productA)->create(['is_outlet' => true]);

    $productB = Product::factory()->create(['offer_price' => 40.00]);
    $skuB = ProductSku::factory()->for($productB)->create(['is_outlet' => true]);

    $component = Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $campaign,
        'pageClass' => EditCampaign::class,
    ]);

    $component->callTableBulkAction('associateSelected', [$productA, $productB]);

    $campaign->refresh();
    expect($campaign->products->modelKeys())->toEqualCanonicalizing([$productA->id, $productB->id])
        ->and($campaign->outletSkus->modelKeys())->toEqualCanonicalizing([$skuA->id, $skuB->id]);

    $component->callTableBulkAction('detachSelected', [$productA, $productB]);

    $campaign->refresh();
    expect($campaign->products)->toBeEmpty()
        ->and($campaign->outletSkus)->toBeEmpty();
});
