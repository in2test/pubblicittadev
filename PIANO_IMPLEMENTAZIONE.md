# 📋 Implementation Plan & System Architecture — Pubblicittà24

**Current Status**: 🚀 PRODUCTION READY (Completed & Local SEO Active)
**System Date**: September 30, 2026
**Core Scope**: E-commerce platform with automated Stripe payments, B2B manual quotation flows, NewWave API automated inventory sync, and hyper-targeted Local SEO for the Ciociaria region.

## 🧪 Test Coverage Status

* **Test Suite Status**: ✅ 292 passing tests (666+ assertions) across unit, feature, integration, and architecture suites. All critical services and features are covered.

## 📊 Project Overview & Architecture

### 🧬 Core Ecosystem

* **Name**: Pubblicittà24 Platform (Custom & Standard Prints, Rigid Media, Promotional Apparel).
* **Fulfillment Vectors**: Authenticated Checkout (Stripe Webhooks) **OR** Private Corporate B2B Quote Workflow. (Note: Guest checkout is disabled; account creation is strictly required for order placement).
* **Tech Stack**: Laravel 13, Livewire 4, Filament 5, Volt 1, Tailwind CSS 4, Spatie Media Library 11, Laravel Scout 11.

### 📐 SOLID Design & Architectural Refactor Rules

To maintain code health, the system strictly enforces the following design rules verified by a PEST/PHPUnit architecture suite:

1. **Zero-Fat Controllers**: Controllers are banned from calling the `Mail` facade directly; operations are entirely delegated to dedicated Domain Services via Container Resolution.
2. **Clean Domain Models**: Models do not interact with Stripe or Mail infrastructure. Outbound calls are fully encapsulated.
3. **Encapsulated Queries**: Database queries prioritize Eloquent relationships. Raw DB queries are restricted to performance-critical calculation boundaries.

---

## 🛡️ Operations, Compliance & Security (Active Workflows)

### 1. Accounting & Invoicing Workflow

* **Current Process**: Orders are captured via Stripe (B2C) or approved via Quote (B2B). Billing data is collected during checkout.
* **Danea EasyFatt Integration**: Invoices are generated manually off-platform using Danea EasyFatt software.
* **SDI & Delivery**: Invoices are transmitted to the *Sistema di Interscambio* (SDI) via Danea, and a courtesy PDF is subsequently emailed to the client.

### 2. Print File Ingestion Strategy

* **Current Off-Platform Flow**: To bypass malware vectors, server storage limits, and massive timeout configurations, file uploads are **not** currently handled natively on the platform.
* **Client Instructions**: Post-checkout, clients are instructed via order confirmation to submit high-resolution production files (PDF/TIFF) via direct Email or WeTransfer, referencing their specific `order_number`.

### 3. GDPR & Data Lifecycle Management

* **Consent Management**: Active native cookie banner segregating functional storage from tracking analytics (Options: *Accetta Tutto* vs. *Solo Essenziali*).
* **Right to be Forgotten (Account Deletion Strategy)**: *Pending Implementation priority.* Requires a programmatic routine (e.g., a dedicated `UserAnonymizationService`) that triggers upon user deletion request:
* Scrambles or nullifies Personally Identifiable Information (PII) in the `users` and `addresses` tables.
* Maintains `orders` and `order_items` records intact with an anonymized reference (e.g., `user_id` set to null, names converted to "Utente Cancellato") to preserve historical revenue data for Danea EasyFatt tax reporting.

### 4. Infrastructure Reliability

* **Asynchronous Error Tracking**: Because email dispatches rely on Laravel's `defer()` post-transaction, unhandled exceptions do not block the user but will fail silently. Integration of a logging/tracking service (e.g., Sentry or Flare) is required to monitor deferred task failures.
* **Data Backups**: Automated nightly dumps of the PostgreSQL/MySQL database and Spatie MediaLibrary assets stored in an isolated off-site environment.

---

## 🗄️ Normalized Database Schema

### 📦 Core Catalog & Taxonomy

* `categories`: `id`, `name`, `slug`, `parent_id`, `description`, `is_active`, `display_mode`
* `products`: `id`, `name`, `slug`, `sku`, `description`, `category_id`, `is_featured`, `type` (`standard`|`newwave`), `pricing_model` (`fixed`|`quantity`|`area`), `min_area`, `max_width`, `max_height`, `sheet_width`, `sheet_height`, `allows_custom_size`, `min_custom_width`, `max_custom_width`, `min_custom_height`, `max_custom_height`, `sync_status`, `sync_progress`, `synced_at`, `is_active`, `override_price`, `override_description`, `remote_images` (JSON), `price`, `offer_price`, `created_at`, `updated_at`

### 📦 Pricing Matrices & Logic Rules

* `pricing_tiers`: `id`, `product_id`, `product_sku_id`, `is_custom_price`, `min_quantity`, `max_quantity`, `price_per_unit`
* `category_quantity_discounts`: `id`, `category_id`, `min_quantity`, `max_quantity`, `discount_type` (`percent`|`fixed`), `discount_value`, `description`
* `shipping_tiers`: `id`, `name`, `min_order_total`, `cost`, `is_active`

### 📦 Sales, Actions, & Operations

* `addresses`: `id`, `user_id` (FK), `type` (`shipping`|`billing`), `name`, `street`, `city`, `state`, `zip`, `country`, `phone`, `vat_number`, `fiscal_code`, `sdi_code`, `pec_email`, `is_default`
* `orders`: `id`, `user_id` (FK), `order_number`, `payment_status` (Backed Enum), `work_status` (Backed Enum), `total_price`, `total_items`, `shipping_cost`, `shipping_method`, `shipping_address_id`, `billing_address_id`, `stripe_session_id`, `stripe_payment_intent_id`, `paid_at`, `notes`
* `order_items`: `id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `customization_json` (JSON features), `external_file_reference` (Notes for WeTransfer/Email tracking), `work_status`

---

## 🛠️ Implemented Systems Log

### Core Engine Redesign

* **Decoupled Model Behaviors**: Extracted image processing actions from the core `Product` model into an independent, testable `ProductGalleryService`.
* **Deconstructed Sync Routines**: Split the monolithic `ProductSynchronizer` engine into four single-responsibility sub-services: Metadata, Images, SKU/Variations, and Real-Time Availability.
* **Query Optimization**: Eradicated N+1 query loops inside `CartController::index()` via a structural Cart Presenter Query Service that preloads variations, matrix scales, and prices in a single batch.
* **Asynchronous-like Side-Effects**: Shifted email dispatches out of raw Eloquent saving states. Uses Laravel's native `defer()` container wrapper post-database transaction.
* **Enums Integration**: Replaced string states with native PHP Backed Enums (`PaymentStatus`, `WorkStatus`) built with weight matrices for status sorting and localized descriptive strings (`->label()`).
* **Performance Indexes Applied**: `images(product_id, variation_option_id)`, `orders(user_id, created_at)`, `product_skus(product_id, sku)`, `products(category_id, is_active)`.

### Feature Integrations & Upgrades

* **Outlet Cascade System**: Implemented an advanced configuration modal in the Filament admin panel. Allows admins to assign `is_outlet` status and prices globally to a Product or targeted to specific exposed variants. These settings cascade to update all underlying `ProductSku` permutations. On product pages, an unselected product displays the minimum outlet price and badge when available; an explicitly selected variant displays only that SKU's price and outlet status.
* **Multi-SKU Quantity Discount Resolution**: Refactored the `ProductPriceCalculator` to handle volume discounts across multi-SKU products (e.g., buying 10 Small and 10 Large shirts of the same color). Evaluates discount tiers based on aggregate cart quantity.
* **Authenticated GraphQL Gateway (NewWave API)**: Lazy-sync connection handling matching remote inventory balances every 12 hours. Fast stock polling is configured to bypass massive dataset recalculations.
* **Hybrid Media Asset Pipeline**: Merged local uploads handled by Spatie MediaLibrary with remote layout images using selective color queries (`?colore=XX`).
* **User Dashboard (Order History)**: Account area allowing clients to review past orders. Reordering is manual due to the high-variance, custom nature of the printed goods.

---

## 📍 Local SEO Strategy (Fiuggi & Ciociaria Dominance)

### 1. Geolocated Header Structures

* **Home Target Hook**: `Pubblicittà24 | Stampa Digitale, Grande Formato e Abbigliamento a Fiuggi`
* **Home Snippet**: `Professional digital printing in Fiuggi and Frosinone province: business cards, flyers, banners, Forex panels, gadgets, and custom apparel. Free online quotes.`
* **Targeted Landing Directories**: `/stampa-digitale-fiuggi`, `/stampa-grande-formato-fiuggi`, `/abbigliamento-lavoro-fiuggi`.

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

---

## 🎯 Controllers & Routes

| Route | Controller / Handler | Purpose |
| --- | --- | --- |
| `/` | `HomePageController` | Homepage with featured products |
| `/catalogo` | `CategoryController` | Browse all categories |
| `/catalogo/{category:slug}` | `CategoryController` | Category page with filters & subcategories |
| `/catalogo/{category:slug}/{product:slug}` | `ProductController` | Product detail page |
| `/search` | `SearchController` | Product search via Laravel Scout |
| `/cart` | `CartController` | Cart view |
| `/cart/add`, `/cart/update`, `/cart/remove`, `/cart/clear` | `CartController` | Cart mutations & calculations |
| `/checkout` | `pages.checkout` (Volt) | Authenticated Checkout form |
| `/checkout/session`, `/checkout/quotation`, `/checkout/success` | `CheckoutController` | Stripe checkout session & B2B quotation processing |
| `/dashboard` | `DashboardController` | Order History & Profile Area |
| `/dashboard/orders`, `/dashboard/orders/{order}` | `DashboardController` | Customer orders listing and detail view |
| `/feed/google-merchant.xml` | `GoogleMerchantFeedController` | Feed for Google Shopping |
| `/sitemap.xml` | `SitemapController` | Dynamic XML sitemap |
| `/outlet` | `OutletProducts` (Livewire) | Outlet products page |
| `/webhooks/stripe` | `WebhookController` | Stripe webhook listener for checkout.session.completed |

---

## 🧪 Quality Assurance & Test Coverage

The platform runs a comprehensive Pest PHP test suite featuring **292 automated unit, feature, and architecture tests with 666+ assertions** (100% passing). The latest full test run passed, and Rector, Pint, and Larastan completed without errors:

* **Functional Coverage**: Suites for `OutletPricingTest`, `OutletPageTest`, `MultiSkuQuantityDiscountTest`, `ProductStartingPriceServiceTest`, `CategoryQuantityDiscountTest`, `ProductPriceCalculatorTest`, `CheckoutTest`, and `CartTest` ensure that pricing cascade, volume aggregates, and checkout flows do not regress.
* **Architecture & Integrity**: Strict compliance rules including Laravel presets (`tests/Architecture/PresetTest.php`) and domain separation.

### 📊 Current Status

* **Total Models**: 18
* **Total Services**: 21
* **Automated Tests**: 292 passing tests (666+ assertions) across Unit, Feature, and Architecture suites.

### ✅ Tests Already Created & Active

| Test Suite / Area | Status | File Path |
| --- | --- | --- |
| PresetTest (Architecture) | ✅ Passing | `tests/Architecture/PresetTest.php` |
| ProductPriceCalculatorTest | ✅ Passing | `tests/Unit/ProductPriceCalculatorTest.php` |
| ProductPricingServiceTest | ✅ Passing | `tests/Unit/ProductPricingServiceTest.php` |
| QuantityDiscountServiceTest | ✅ Passing | `tests/Unit/QuantityDiscountServiceTest.php` |
| PricingTierTest | ✅ Passing | `tests/Unit/PricingTierTest.php` |
| ProductSkuTest | ✅ Passing | `tests/Unit/ProductSkuTest.php` |
| ProductStartingPriceServiceTest | ✅ Passing | `tests/Unit/ProductStartingPriceServiceTest.php` |
| CategoryQuantityDiscountTest | ✅ Passing | `tests/Unit/CategoryQuantityDiscountTest.php` |
| CartManagerTest & CartPricingTest | ✅ Passing | `tests/Unit/CartManagerTest.php`, `tests/Unit/CartPricingTest.php` |
| MultiSkuQuantityDiscountTest | ✅ Passing | `tests/Unit/MultiSkuQuantityDiscountTest.php` |
| ProductDiscountTest | ✅ Passing | `tests/Unit/ProductDiscountTest.php` |
| CheckoutTest (Stripe & Quotation) | ✅ Passing | `tests/Feature/CheckoutTest.php` |
| CartTest & CartViewTest | ✅ Passing | `tests/Feature/CartTest.php`, `tests/Feature/CartViewTest.php` |
| WebhookTest (Stripe webhook) | ✅ Passing | `tests/Feature/WebhookTest.php` |
| ProductSyncTest (NewWave API) | ✅ Passing | `tests/Feature/ProductSyncTest.php` |
| ProductGalleryImagesTest | ✅ Passing | `tests/Feature/ProductGalleryImagesTest.php` |
| Outlet display, OutletPageTest & OutletPricingTest | ✅ Passing | `tests/Feature/ProductOutletDisplayTest.php`, `tests/Feature/OutletPageTest.php`, `tests/Unit/OutletPricingTest.php` |
| EditJobTest (Custom dimensions & cart edit) | ✅ Passing | `tests/Feature/EditJobTest.php` |
| CatalogTest & ProductPageTest | ✅ Passing | `tests/Feature/CatalogTest.php`, `tests/Feature/ProductPageTest.php` |
| OrderNotificationsTest & AdminNotificationsTest | ✅ Passing | `tests/Feature/OrderNotificationsTest.php`, `tests/Feature/AdminNotificationsTest.php` |
| OrderInvoicesAndTrackingTest | ✅ Passing | `tests/Feature/OrderInvoicesAndTrackingTest.php` |
| Filament Resource Tests (Categories, Products, Users, VariationTypes, Media) | ✅ Passing | `tests/Feature/Filament/*` |
| Auth & Security Suites (Fortify, 2FA, Verification, Reset) | ✅ Passing | `tests/Feature/Auth/*`, `tests/Feature/Settings/*` |
| Google Merchant Feed & Sitemap | ✅ Passing | `tests/Feature/GoogleMerchantFeedTest.php`, `tests/Feature/SitemapTest.php` |

### ❌ Remaining / Future Test Opportunities

The following test suites represent planned future additions or specialized edge cases:

1. `UserAnonymizationServiceTest` (To be implemented when dedicated GDPR deletion & tax data anonymization service is built)
2. `ProductCatalogLoadTimeBenchmarkTest` (Automated latency & load benchmarking)
3. `DatabaseQueryPerformanceBenchmarkTest` (Automated query count / N+1 regression assertions under high data volume)

---

## 🛠️ Development Workflow & Tooling

1. **Environment**: Windows 11 Pro / PowerShell.
2. **Quality & Sanity Tools**:
   * **Pint** (`vendor/bin/pint`): Code styling and formatting (PSR-12).
   * **Rector** (`vendor/bin/rector`): Automated refactoring and PHP/Laravel upgrades.
   * **Larastan** (`vendor/bin/phpstan analyse`): Static analysis for type safety and bug detection.
   * **Sloppy** (`vendor/bin/sloppy`): Architectural rule verification, god method/class detection, and code sanity inspection.
   * **Sheath** (`php artisan sheath:lint`): Blade template analyzer by Forte.
   * Shortcut command: `composer run format` to run formatting and static checks.
3. **Git Workflow**: Pass tests ➔ Format Code ➔ Stage ➔ Commit ➔ Push to `master`. Pushing to the `production` branch automatically triggers the GitHub Action deployment pipeline.
