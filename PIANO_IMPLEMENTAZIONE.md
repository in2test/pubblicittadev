# 📋 Implementation Plan & System Architecture — Pubblicittà24

**Current Status**: 🚀 PRODUCTION READY (Completed & Local SEO Active)  
**System Date**: September 18, 2026  
**Core Scope**: E-commerce platform with automated Stripe payments, B2B manual quotation flows, NewWave API automated inventory sync, and hyper-targeted Local SEO for the Ciociaria region.

---

## 🚨 ACTIVE ISSUES & OPEN TICKETS

### 🔴 [BUG] Cross-Item Cart Quantity Tier Discount Failure
*   **Symptom**: When a user adds different variants of the same base product to the cart (e.g., 8 Orange T-Shirts in size M and 7 in size XL), the volume discount (triggered at ≥ 10 units) fails to apply upon cart updating. However, adding 8 Ms and 10 XLs simultaneously from the product detail page correctly displays the discounted price.
*   **Impact**: Breaks pricing logic on multi-variant mixed item orders inside `CartController` or `QuantityDiscountService`.
*   **Task**: Refactor the item group accumulation loop in the cart pricing pre-loader to aggregate base product IDs or categories before checking tier thresholds.

### Solution to be implemented
*Bug Fix: Quantity Discount Highlighting & Application for Multi-SKU Products
Background
For products like "Basic T" (type: newwave), users can select quantities across multiple sizes (SKUs): e.g., 8×XS, 4×S, 5×M = 17 total units. The 5% discount should activate at ≥10 units.

The UI correctly highlights the discount tier badge when the total crosses the threshold, but the price shown and applied is wrong — it uses the per-SKU quantity (e.g., 5 for M) to look up the discount instead of the grand total. The discount is only actually applied when a single size reaches the threshold alone.

The same fault exists in the cart (CartPresenter) when calculating savings, and in the addToCart payload which stores an incorrect per-unit price.

Root Causes
Bug 1 — totalQuantity() Computed Property (Product Page, ⚡product.blade.php)
php

// Lines 367–382
public function totalQuantity(): int
{
    $product = $this->product();
    if ($product->type === 'newwave') {
        return array_sum(array_map(intval(...), $this->quantities));  // ✅ correct for newwave
    }
    // For standard products: returns only the active SKU's qty — misses other sizes
    $activeSku = ...
    return $activeSku ? (int) ($this->quantities[$activeSku->id] ?? 0) : 0;
}
newwave products correctly sum all sizes in $this->quantities. ✅

However, ProductPriceCalculator::calculateTotalPrice() receives $totalQuantity as the aggregate, but also receives $skuQuantities (all individual sizes). Inside the calculator, each SKU is priced using calculateFinalUnitPrice($product, $skuQty, ...) — meaning discount lookup uses the per-SKU qty, not the grand total.

Bug 2 — ProductPriceCalculator::calculateTotalPrice() — Per-SKU Discount Lookup
php

// Lines 90–111 — the loop that builds the total
foreach ($skuQuantities as $skuId => $rawQty) {
    $skuQty = (int) $rawQty;
    if ($skuQty > 0) {
        $sku = ...;
        // ❌ BUG: passes $skuQty (e.g. 5 for M), not $totalQuantity (17)
        $unitPrice = $this->calculateFinalUnitPrice($product, $skuQty, null, null, $sku);
        ...
        $total += $unitPrice * $skuQty;
    }
}
calculateFinalUnitPrice calls getPriceForQuantity($product, $quantity) which then calls QuantityDiscountService::calculatePrice($product, $quantity) — using the wrong quantity (5 instead of 17).

Fix: Pass $totalQuantity to calculateFinalUnitPrice for the discount lookup, while still multiplying by $skuQty for the line total.

Bug 3 — ⚡product.blade.php Discount Highlighting vs. Actual Price
The highlighting at line 779–782 correctly uses $this->totalQuantity (the grand total). ✅

But as shown in Bug 2, the price calculation uses per-SKU qty for discount lookup. So the badge appears active but the price charged is undiscounted. This is fixed by Bug 2.

Bug 4 — Cart CartPresenter::present() — basePrice for Multi-SKU Items
In CartPresenter::present() (line 130–133), $basePrice is derived from the active SKU or product price. But is_discounted (line 198) and totalSavings (line 216) compare $discPrice < $basePrice. Since $discPrice is already incorrectly calculated (Bug 2), even after fixing Bug 2 the basePrice comparison needs to be consistent with a "no discount" reference price, not the already-discounted price.

NOTE

After fixing Bug 2, CartPresenter should work correctly because disc_price will properly reflect the discounted unit price. The is_discounted and totalSavings comparisons are logically sound already — they just need the upstream price to be fixed.

Bug 5 — addToCart Stores Wrong Unit Price
In ⚡product.blade.php line 584:

php

'price' => ($this->totalPrice() / $this->totalQuantity()),
This stores the calculated (potentially wrong) unit price in the session. Once Bug 2 is fixed, this will naturally store the correct discounted unit price.

Proposed Changes
1. app/Services/ProductPriceCalculator.php
[MODIFY] 
ProductPriceCalculator.php
Change the foreach ($skuQuantities ...) loop so that the discount is looked up using $totalQuantity (grand total across all sizes), while the line total is still multiplied by $skuQty:

diff

 foreach ($skuQuantities as $skuId => $rawQty) {
     $skuQty = (int) $rawQty;
     if ($skuQty > 0) {
         $sku = $product->skus->firstWhere('id', $skuId);
         if ($isCustomFormat && $nearestSku instanceof ProductSku) {
             $sku = $nearestSku;
         }
-        $unitPrice = $this->calculateFinalUnitPrice($product, $skuQty, null, null, $sku);
+        // Use $totalQuantity for discount tier lookup; $skuQty is only used for line subtotal
+        $unitPrice = $this->calculateFinalUnitPrice($product, $totalQuantity, null, null, $sku);
         if ($sku && $sku->override_price !== null) {
             $unitPrice = (float) $sku->override_price;
         }
         if ($isCustomFormat) {
             $unitPrice *= 1.20;
         }
         $total += $unitPrice * $skuQty;
     }
 }
This is the core fix. One line change with clear intent.

Verification Plan
Scenario: 8 XS + 4 S + 5 M (total 17, above the 10-unit threshold)
Before fix	After fix
Badge highlighted ✅, price still at full rate ❌	Badge highlighted ✅, price discounted ✅
XS priced at qty=8 → no discount	XS priced at qty=17 → 5% discount
S priced at qty=4 → no discount	S priced at qty=17 → 5% discount
M priced at qty=5 → no discount	M priced at qty=17 → 5% discount
Scenario: 10 XS only
Before & after
No change — already worked correctly (single SKU = skuQty == totalQuantity)
Automated Tests
bash

php artisan test --compact --filter=ProductPriceCalculator
php artisan test --compact --filter=CartPresenter
php artisan test --compact --filter=QuantityDiscount
After the fix, run the full suite:

bash

php artisan test --compact
Manual Verification
Navigate to Basic-T product page
Set 8 XS + 4 S + 5 M (total = 17)
Verify price shows discounted rate (5% off) — previously showed full price €6.90, should now show ~€6.55
Add to cart
In cart, verify is_discounted flag is true and savings are displayed
Test boundary: reduce M from 5 to 2 (total = 14) → still discounted. Reduce XS to 1 (total = 7) → discount removed
Impact Assessment
Scope: Single method, single line change in ProductPriceCalculator
Risk: Low — totalQuantity is already passed into calculateTotalPrice and is correct for both single-SKU and multi-SKU scenarios
No schema changes needed
No new files needed
IMPORTANT

After fixing, check whether existing Pest tests for ProductPriceCalculator cover multi-SKU discount scenarios. If not, add a test that asserts the discount is applied using the total qty across all SKUs.*

---

## 📊 Project Overview & Architecture

### 🧬 Core Ecosystem
*   **Name**: Pubblicittà24 Platform (Custom & Standard Prints, Rigid Media, Promotional Apparel).
*   **Fulfillment Vectors**: Instant Checkout (Stripe Webhooks) **OR** Private Corporate B2B Quote Workflow.
*   **Tech Stack**: Laravel 13, Livewire 4, Filament 5, Volt 1, Tailwind CSS 4, Spatie Media Library 11, Laravel Scout 11.

### 📐 SOLID Design & Architectural Refactor Rules
To maintain code health, the system strictly enforces the following design rules verified by a PEST/PHPUnit architecture suite:
1.  **Zero-Fat Controllers**: Controllers are banned from calling the `Mail` facade directly; operations are entirely delegated to dedicated Domain Services via Container Resolution.
2.  **Clean Domain Models**: Models do not interact with Stripe or Mail infrastructure. Outbound calls are fully encapsulated.
3.  **Encapsulated Queries**: Database queries prioritize Eloquent relationships. Raw DB queries are restricted to performance-critical calculation boundaries.

---

## 🗄️ Normalized Database Schema

### 📦 Core Catalog & Taxonomy
*   `categories`: `id`, `name`, `slug`, `parent_id`, `description`, `is_active`, `display_mode`
*   `products`: `id`, `name`, `slug`, `sku`, `description`, `category_id`, `is_featured`, `type` (`standard`|`newwave`), `pricing_model` (`fixed`|`quantity`|`area`), `min_area`, `max_width`, `max_height`, `sheet_width`, `sheet_height`, `allows_custom_size`, `min_custom_width`, `max_custom_width`, `min_custom_height`, `max_custom_height`, `sync_status`, `sync_progress`, `synced_at`, `is_active`, `override_price`, `override_description`, `remote_images` (JSON), `price`, `offer_price`, `created_at`, `updated_at`

### 📦 Pricing Matrices & Logic Rules
*   `pricing_tiers`: (Granular price-per-unit variation mapping table)  
    `id`, `product_id`, `product_sku_id`, `is_custom_price`, `min_quantity`, `max_quantity`, `price_per_unit`
*   `category_quantity_discounts`: (Fallback cascading category discounts)  
    `id`, `category_id`, `min_quantity`, `max_quantity`, `discount_type` (`percent`|`fixed`), `discount_value`, `description`
*   `shipping_tiers`: `id`, `name`, `min_order_total`, `cost`, `is_active`

### 📦 Sales, Actions, & Operations
*   `addresses`: (Unified corporate/consumer identities)  
    `id`, `user_id` (FK), `type` (`shipping`|`billing`), `name`, `street`, `city`, `state`, `zip`, `country`, `phone`, `vat_number` (Partita IVA), `fiscal_code` (Codice Fiscale), `sdi_code`, `pec_email`, `is_default`
*   `orders`: `id`, `user_id` (FK), `order_number`, `payment_status` (Backed Enum), `work_status` (Backed Enum), `total_price`, `total_items`, `shipping_cost`, `shipping_method`, `shipping_address_id`, `billing_address_id`, `stripe_session_id`, `stripe_payment_intent_id`, `paid_at`, `notes`
*   `order_items`: (Represents distinct structural print jobs tied to a UUID)  
    `id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `customization_json` (JSON features), `design_file_path`, `work_status`

---

## 🛠️ Implemented Systems Log

### Core Core Engine Redesign
*   **Decoupled Model Behaviors**: Extracted image processing actions from the core `Product` model into an independent, testable `ProductGalleryService`.
*   **Deconstructed Sync Routines**: Split the monolithic `ProductSynchronizer` engine into four single-responsibility sub-services: Metadata, Images, SKU/Variations, and Real-Time Availability.
*   **Query Optimization**: Eradicated N+1 query loops inside `CartController::index()` via a structural Cart Presenter Query Service that preloads variations, matrix scales, and prices in a single batch.
*   **Asynchronous-like Side-Effects**: Shifted email dispatches out of raw Eloquent saving states. Uses Laravel's native `defer()` container wrapper post-database transaction, bypassing thread blocks without an active Redis queue layout.
*   **Enums Integration**: Replaced string states with native PHP Backed Enums (`PaymentStatus`, `WorkStatus`) built with weight matrices for status sorting and localized Italian descriptive strings (`->label()`).
*   **Performance Indexes Applied**:
    *   `images(product_id, variation_option_id)`
    *   `orders(user_id, created_at)`
    *   `product_skus(product_id, sku)`
    *   `products(category_id, is_active)`

### Integration Modules
*   **Authenticated GraphQL Gateway (NewWave API)**: Complete lazy-sync connection handling matching remote inventory balances every 12 hours. Fast stock polling is configured to bypass massive dataset recalculations.
*   **Hybrid Media Asset Pipeline**: Merged local uploads handled by Spatie MediaLibrary with remote layout images using selective color queries (`?colore=XX`) to change variants dynamically.
*   **B2C/B2B Financial Funnel**: Standard Stripe Checkout processing with secure webhook receivers, running parallel with an optional alternative custom checkout request loop for custom quotes.
*   **Marketing & Discovery Endpoints**: Real-time generation of Google Merchant XML Feed at `/feed/google-merchant.xml` alongside dynamic, un-cached sitemap files at `/sitemap.xml`.

---

## 📍 Local SEO Strategy (Fiuggi & Ciociaria Dominance)

### 1. Geolocated Header Structures
*   **Home Target Hook**: `Pubblicittà24 | Stampa Digitale, Grande Formato e Abbigliamento a Fiuggi`
*   **Home Snippet**: `Stampa digitale professionale a Fiuggi e provincia di Frosinone: biglietti da visita, volantini, striscioni, pannelli Forex, gadget e abbigliamento personalizzato. Preventivi gratuiti online.`
*   **Targeted Landing Directories**:
    *   `/stampa-digitale-fiuggi`: Business stationaries, cards, flyers, and event brochures.
    *   `/stampa-grande-formato-fiuggi`: Structural PVC banners, mesh partitions, Forex/Plexiglas sheets, and custom rollups.
    *   `/abbigliamento-lavoro-fiuggi`: Corporate wear, safety apparel, and custom uniforms for thermal spas, medical centers, and hospitality sectors.

### 2. Semantic Graph Implementation (`Schema.org`)
Configured globally inside `resources/views/layouts/layout.blade.php`:

```json
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
    "name": "Servizi di Stampa e Personalizzazione",
    "itemListElement": [
      "Stampa Digitale",
      "Biglietti da Visita",
      "Stampa Grande Formato",
      "Insegne",
      "Pannelli Rigidi",
      "Abbigliamento Promozionale",
      "Abbigliamento da Lavoro"
    ]
  }
}
```

### 3. Footprint Aggregations
*   **Footer Block**: Static contextual map references, localized microcopy (*"Servizio di Stampa e Personalizzazione a Fiuggi e in Provincia di Frosinone"*), and direct shortcuts linking back to our Google Business Profile page to maximize local map pack authority.

---

## 🧪 Quality Assurance & Test Coverage

The platform runs a PEST test suite featuring **190+ automated unit and architecture assertions**:
*   **Functional Cover**: Complete cart item mutations, variant queries, discount engine bounds, automated XML schema validation for Google Merchant feeds, and localized SEO query parameters.
*   **Architecture Isolation Enforcements**: Asserts strict compliance with project rules (e.g., `ControllersDoNotSendMailDirectly.php`, `ModelsDoNotDependOnExternalServices.php`, and `ExternalApiCallsLiveInServices.php`).
