# 📋 Implementation Plan & System Architecture — Pubblicittà24

**Current Status**: 🚀 PRODUCTION READY (Completed & Local SEO Active)

**System Date**: September 25, 2026

**Core Scope**: E-commerce platform with automated Stripe payments, B2B manual quotation flows, NewWave API automated inventory sync, and hyper-targeted Local SEO for the Ciociaria region.

## 📊 Project Overview & Architecture

### 🧬 Core Ecosystem

- **Name**: Pubblicittà24 Platform (Custom & Standard Prints, Rigid Media, Promotional Apparel).

- **Fulfillment Vectors**: Instant Checkout (Stripe Webhooks) **OR** Private Corporate B2B Quote Workflow.

- **Tech Stack**: Laravel 13, Livewire 4, Filament 5, Volt 1, Tailwind CSS 4, Spatie Media Library 11, Laravel Scout 11.

### 📐 SOLID Design & Architectural Refactor Rules

To maintain code health, the system strictly enforces the following design rules verified by a PEST/PHPUnit architecture suite:

1. **Zero-Fat Controllers**: Controllers are banned from calling the `Mail` facade directly; operations are entirely delegated to dedicated Domain Services via Container Resolution.

2. **Clean Domain Models**: Models do not interact with Stripe or Mail infrastructure. Outbound calls are fully encapsulated.

3. **Encapsulated Queries**: Database queries prioritize Eloquent relationships. Raw DB queries are restricted to performance-critical calculation boundaries.

## 🗄️ Normalized Database Schema

### 📦 Core Catalog & Taxonomy

- `categories`: `id`, `name`, `slug`, `parent_id`, `description`, `is_active`, `display_mode`

- `products`: `id`, `name`, `slug`, `sku`, `description`, `category_id`, `is_featured`, `type` (`standard`|`newwave`), `pricing_model` (`fixed`|`quantity`|`area`), `min_area`, `max_width`, `max_height`, `sheet_width`, `sheet_height`, `allows_custom_size`, `min_custom_width`, `max_custom_width`, `min_custom_height`, `max_custom_height`, `sync_status`, `sync_progress`, `synced_at`, `is_active`, `override_price`, `override_description`, `remote_images` (JSON), `price`, `offer_price`, `created_at`, `updated_at`

### 📦 Pricing Matrices & Logic Rules

- `pricing_tiers`: (Granular price-per-unit variation mapping table)

    `id`, `product_id`, `product_sku_id`, `is_custom_price`, `min_quantity`, `max_quantity`, `price_per_unit`

- `category_quantity_discounts`: (Fallback cascading category discounts)

    `id`, `category_id`, `min_quantity`, `max_quantity`, `discount_type` (`percent`|`fixed`), `discount_value`, `description`

- `shipping_tiers`: `id`, `name`, `min_order_total`, `cost`, `is_active`

### 📦 Sales, Actions, & Operations

- `addresses`: (Unified corporate/consumer identities)

    `id`, `user_id` (FK), `type` (`shipping`|`billing`), `name`, `street`, `city`, `state`, `zip`, `country`, `phone`, `vat_number`, `fiscal_code`, `sdi_code`, `pec_email`, `is_default`

- `orders`: `id`, `user_id` (FK), `order_number`, `payment_status` (Backed Enum), `work_status` (Backed Enum), `total_price`, `total_items`, `shipping_cost`, `shipping_method`, `shipping_address_id`, `billing_address_id`, `stripe_session_id`, `stripe_payment_intent_id`, `paid_at`, `notes`

- `order_items`: (Represents distinct structural print jobs tied to a UUID)

    `id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `customization_json` (JSON features), `design_file_path`, `work_status`

## 🛠️ Implemented Systems Log

### Core Engine Redesign

- **Decoupled Model Behaviors**: Extracted image processing actions from the core `Product` model into an independent, testable `ProductGalleryService`.

- **Deconstructed Sync Routines**: Split the monolithic `ProductSynchronizer` engine into four single-responsibility sub-services: Metadata, Images, SKU/Variations, and Real-Time Availability.

- **Query Optimization**: Eradicated N+1 query loops inside `CartController::index()` via a structural Cart Presenter Query Service that preloads variations, matrix scales, and prices in a single batch.

- **Asynchronous-like Side-Effects**: Shifted email dispatches out of raw Eloquent saving states. Uses Laravel's native `defer()` container wrapper post-database transaction, bypassing thread blocks without an active Redis queue layout.

- **Enums Integration**: Replaced string states with native PHP Backed Enums (`PaymentStatus`, `WorkStatus`) built with weight matrices for status sorting and localized descriptive strings (`->label()`).

- **Performance Indexes Applied**: `images(product_id, variation_option_id)`, `orders(user_id, created_at)`, `product_skus(product_id, sku)`, `products(category_id, is_active)`.

### Feature Integrations & Upgrades

- **Outlet Cascade System**: Implemented an advanced configuration modal in the Filament admin panel. Allows admins to assign `is_outlet` status and prices either globally to a Product or targeted to specific exposed variants (e.g., `Color: White`). These settings automatically cascade to update all underlying `ProductSku` permutations, reflecting dynamic visual badges and prices on the frontend swatches.

- **Multi-SKU Quantity Discount Resolution**: Refactored the `ProductPriceCalculator` to accurately handle volume discounts across multi-SKU products (e.g., buying 10 Small and 10 Large shirts of the same color). The system now evaluates discount tiers based on aggregate cart quantity (`$totalQuantity`), while correctly calculating the line subtotal using the specific `$skuQty`.

- **Authenticated GraphQL Gateway (NewWave API)**: Complete lazy-sync connection handling matching remote inventory balances every 12 hours. Fast stock polling is configured to bypass massive dataset recalculations.

- **Hybrid Media Asset Pipeline**: Merged local uploads handled by Spatie MediaLibrary with remote layout images using selective color queries (`?colore=XX`) to change variants dynamically.

- **Marketing & Discovery Endpoints**: Real-time generation of Google Merchant XML Feed at `/feed/google-merchant.xml` alongside dynamic, un-cached sitemap files at `/sitemap.xml`.

## 📍 Local SEO Strategy (Fiuggi & Ciociaria Dominance)

### 1. Geolocated Header Structures

- **Home Target Hook**: `Pubblicittà24 | Digital Printing, Large Format & Apparel in Fiuggi`

- **Home Snippet**: `Professional digital printing in Fiuggi and Frosinone province: business cards, flyers, banners, Forex panels, gadgets, and custom apparel. Free online quotes.`

- **Targeted Landing Directories**: `/stampa-digitale-fiuggi`, `/stampa-grande-formato-fiuggi`, `/abbigliamento-lavoro-fiuggi`.

### 2. Semantic Graph Implementation (`Schema.org`)

Configured globally inside `resources/views/layouts/layout.blade.php`:

```
{
  "@context": "https://schema.org",
  "@type": "PrintShop",
  "name": "Pubblicittà24",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Fiuggi",
    "addressRegion": "FR",
    "addressCountry": "IT"
  },
  "areaServed": [
    "Fiuggi", "Anagni", "Alatri", "Ferentino", "Frosinone",
    "Sora", "Paliano", "Acuto", "Piglio", "Guarcino", "Ciociaria"
  ],
  "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Printing & Customization Services",
    "itemListElement": [
      "Digital Printing",
      "Business Cards",
      "Large Format Printing",
      "Signage",
      "Rigid Panels",
      "Promotional Apparel",
      "Workwear"
    ]
  }
}
```

## 📦 Core Models & Domain Entities

### 🏷️ Product Model (`app/Models/Product.php`)

The heart of the e-commerce platform, representing customizable apparel and print products.

**Key Features**:

- **Dual Types**: `standard` (fixed pricing) and `newwave` (synced inventory from NewWave API)

- **Three Pricing Models**: `fixed`, `quantity`, `area` (square meter-based)

- **Service Decoupling**: Delegates complex logic to dedicated services (Gallery, Sync, Pricing, Starting Prices).

- **Outlet Cascade Support**: Serves as the top-level entity for outlet logic. Administrators configure outlet statuses here (`is_outlet`, `outlet_price`), which then cascade down based on rules (entire product vs. exposed variant specific) to calculate frontend prices via `getMinimumOutletPrice()` and `hasValidOutletPrice()`.

- **Scout Integration**: Laravel Scout searchable for Algolia/Elasticsearch indexing.

### 📋 ProductSku Model

Represents a concrete variant combination (e.g., Size: L + Color: Bianco).

**Key Fields**:

- `id`, `product_id`, `sku`, `quantity`, `is_available`

- `override_price`: Custom price override for this SKU.

- `is_outlet`: A boolean flag inherited from the parent Product or Variation cascade, allowing granular pricing adjustments to reach the final cart calculation.

### 🎨 Variation System

A flexible multi-level variation system supporting:

- **Variation Types** (`variation_types`): Name, presentation type, multi-select allowance, URL exposure rules (`expose_in_url`). URL exposure is critical as it defines which variants can act as targets for selective Outlet pricing cascades.

- **Variation Options** (`variation_options`): Specific option names, color hex values, physical dimensions.

- **Product Variation Types & Options (Pivots)**: Links products to types/options with image flags, modifiers, and price adjustments.

## 🔧 Services Layer

The platform follows strict separation of concerns. Controllers never call the `Mail` facade directly, and models never interact with external APIs or payment gateways.

### 💰 Pricing & Cart Services

- **ProductPricingService & ProductPriceCalculator**: Responsible for resolving final unit prices. These services handle complex logic such as **Multi-SKU Volume Aggregation**. When customers add different sizes or colors of the same product, the engine determines the discount tier using the aggregate total quantity of the parent product, ensuring correct bulk pricing. It then resolves the specific line subtotal using the individual SKU quantity.

- **QuantityDiscountService**: Retrieves applicable quantity discounts with category hierarchy traversal.

- **CartManager & CartPresenter**: Manage cart mutations and state representations. They dynamically attach visual badges and updated prices for SKUs modified by the Outlet cascade, pushing these updates to the frontend configuration UI.

### 📊 Synchronization & Media Services

- **ProductSynchronizer**: Orchestrates the full sync workflow for NewWave products, delegating to specialized metadata, image, SKU, and availability sub-services.

- **ProductGalleryService & ProductMediaSyncService**: Manage image galleries, conversions, and Spatie MediaLibrary synchronization, mapping selective color query logic (`?colore=XX`).

### 💳 Stripe Payment & B2B Quotation Flow

- **Stripe Checkout Flow**: Standard checkout session creation, secure webhook receiver handling `payment_intent.succeeded`, order creation, and confirmation emails.

- **B2B Quotation Flow**: Custom quotation request loop handled via manual admin review and approval in the Filament dashboard.

## 🎯 Controllers & Routes

| Route                        | Controller / Handler           | Purpose                         |
| ---------------------------- | ------------------------------ | ------------------------------- |
| `/`                          | `HomePageController`           | Homepage with featured products |
| `/catalogo`                  | `CategoryController`           | Browse all categories           |
| `/catalogo/{slug}`           | `CategoryController`           | Category page                   |
| `/catalogo/{slug}/{product}` | `ProductController`            | Product detail page             |
| `/cart`                      | `CartController`               | Cart view                       |
| `/cart/add`, `/cart/update`  | `CartController`               | Cart mutations                  |
| `/checkout`                  | Volt component                 | Checkout form                   |
| `/feed/google-merchant.xml`  | `GoogleMerchantFeedController` | Sitemap for Google Shopping     |
| `/sitemap.xml`               | `SitemapController`            | Dynamic sitemap                 |
| `/outlet`                    | `OutletProducts`               | Outlet products page            |

## 🧪 Quality Assurance & Test Coverage

The platform runs a Pest PHP test suite featuring **190+ automated unit and architecture assertions**:

- **Functional Coverage**: Including specific suites for new features like `OutletPricingTest`, `OutletPageTest`, and `MultiSkuQuantityDiscountTest` which ensure the pricing cascade and volume aggregates do not regress.

- **Architecture Enforcement**: Strict compliance rules (e.g., `ControllersDoNotSendMailDirectly.php`, `ModelsDoNotDependOnExternalServices.php`).

## 🛠️ Development Workflow & Tooling

1. **Environment**: Windows 11 Pro / PowerShell.
2. **Quality Tools**: Pint (PSR-12), Rector (Refactoring), Larastan (Static Analysis). Run via `composer run format`. A blade analyser Sheath by Forte. Run via `php artisan sheath:lint`.
3. **Git Workflow**: Pass tests ➔ Format Code ➔ Stage ➔ Commit ➔ Push to `master`. Pushing to the `production` branch automatically triggers the GitHub Action deployment pipeline.

# Test Coverage Gap Analysis TO DO

## 📊 Current Status
- **Total Models**: 20
- **Models with Tests**: ~8 (40%)
- **Total Services**: 23
- **Services with Tests**: ~5 (22%)

---

## 🚨 Critical Gaps (High Priority)

### Services Without Tests

#### 1. `ProductPriceCalculator` (CRITICAL - No Test)
**File**: `app/Services/ProductPriceCalculator.php`
**Responsibility**: Calculates total job prices for cart items, applies modifiers, handles area-based pricing
**Why Critical**: Core pricing logic that affects every order
**Happy Path Needed**:
- Calculate price for fixed pricing model
- Calculate price for quantity pricing model with tiers
- Calculate price for area-based pricing
- Apply percentage/flat modifiers correctly

**Unhappy Path Needed**:
- Product with no pricing tiers
- Invalid quantity (0 or negative)
- Modifier that results in negative price
- Area calculation edge cases

#### 2. `ProductPricingService` (HIGH - No Test)
**File**: `app/Services/ProductPricingService.php`
**Responsibility**: Resolves tier prices based on quantity and SKU, falls back to category discounts
**Happy Path Needed**:
- Resolve price from pricing tier
- Fall back to category discount when no tier matches
- Use product override price when set

**Unhappy Path Needed**:
- Product with no pricing tiers or category
- Invalid quantity outside all tier ranges
- Category without quantity discounts

#### 3. `ProductStartingPriceService` (MEDIUM - Partial Coverage)
**File**: `app/Services/ProductStartingPriceService.php`
**Current Coverage**: Partially tested in `ProductPricingModelTest`
**Missing**:
- Edge cases for different pricing models
- Products with no tiers

#### 4. `QuantityDiscountService` (MEDIUM - Partial Coverage)
**File**: `app/Services/QuantityDiscountService.php`
**Current Coverage**: Tested in `QuantityDiscountServiceTest`
**Missing**:
- Category hierarchy traversal edge cases
- Invalid discount types

---

### Models Without Tests

#### 5. `PricingTier` (HIGH - No Test)
**File**: `app/Models/PricingTier.php`
**Responsibility**: Represents quantity-based pricing tiers
**Happy Path Needed**:
- Create tier with valid min/max quantities
- Validate price is positive
- Save to database

**Unhappy Path Needed**:
- Min quantity greater than max quantity
- Negative price
- Missing required fields

#### 6. `ProductSku` (MEDIUM - No Dedicated Test)
**File**: `app/Models/ProductSku.php`
**Responsibility**: Represents concrete variant combinations
**Happy Path Needed**:
- Create SKU with valid options
- Set availability status
- Set outlet pricing

**Unhappy Path Needed**:
- Invalid option references
- Out of stock quantity
- Outlet price higher than regular price

#### 7. `CategoryQuantityDiscount` (MEDIUM - No Test)
**File**: `app/Models/CategoryQuantityDiscount.php`
**Responsibility**: Fallback quantity discounts for categories
**Happy Path Needed**:
- Create discount with valid type and value
- Set description

**Unhappy Path Needed**:
- Invalid discount type
- Negative discount value

---

### Services Without Tests (Lower Priority)

#### 8. `ProductAdminUrlService` - Generate admin URLs for products
#### 9. `ProductAvailabilitySynchronizer` - Sync availability from NewWave
#### 10. `ProductImageSynchronizer` - Sync images with NewWave
#### 11. `ProductMetadataSynchronizer` - Sync metadata with NewWave
#### 12. `ProductSkuSynchronizer` - Sync SKU data with NewWave
#### 13. `ProductMediaSyncService` - Sync local/remote media
#### 14. `OrderNotificationService` - Send order notifications
#### 15. `OrderPaymentNotificationService` - Send payment notifications

---

## 🧪 Unhappy Path Gaps (Across All Tests)

### Common Missing Scenarios:
1. **Empty cart checkout** - Partially covered in CheckoutTest
2. **Invalid product data** - Rarely tested
3. **Out of stock products** - Not systematically tested
4. **Price calculation edge cases**:
   - Zero quantity
   - Very large quantities
   - Prices at tier boundaries
5. **Authorization failures** - Limited coverage
6. **Database constraint violations** - Not tested

---

## 📝 Recommendations

### Phase 1: Critical Tests (Create Immediately)
1. `ProductPriceCalculatorTest` - Happy + unhappy paths for all pricing models
2. `ProductPricingServiceTest` - Price resolution with fallbacks
3. `PricingTierTest` - CRUD operations and validation
4. `ProductSkuTest` - Variant management and outlet pricing

### Phase 2: Synchronizer Tests
5. `ProductImageSynchronizerTest` - Image sync scenarios
6. `ProductMetadataSynchronizerTest` - Metadata sync scenarios
7. `ProductSkuSynchronizerTest` - SKU sync scenarios
8. `ProductAvailabilitySynchronizerTest` - Availability sync scenarios

### Phase 3: Lower Priority Services
9. `ProductAdminUrlServiceTest`
10. `OrderNotificationServiceTest`
11. `OrderPaymentNotificationServiceTest`
12. And remaining services...

---

## 🎯 Test Creation Strategy

For each new test file, follow this pattern:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\{ServiceName};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it performs happy path scenario', function () {
    // Arrange
    // Act
    // Assert with assertSuccessful()
});

test('it handles edge case: [scenario]', function () {
    // Unhappy path test
});

test('it rejects invalid input: [scenario]', function () {
    // Validation error test
})->throws(\Exception::class, 'Expected error message');
```

---

## ✅ Existing Good Tests (Reference Patterns)

- `CartManagerTest` - Excellent happy + unhappy paths
- `CheckoutTest` - Good authorization tests
- `ProductPricingModelTest` - Good edge case coverage
- `MultiSkuQuantityDiscountTest` - Complex scenario testing
