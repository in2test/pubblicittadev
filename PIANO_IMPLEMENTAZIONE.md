# 📋 Implementation Plan & System Architecture — Pubblicittà24

**Current Status**: 🚀 PRODUCTION READY (Completed & Local SEO Active)
**System Date**: September 29, 2026
**Core Scope**: E-commerce platform with automated Stripe payments, B2B manual quotation flows, NewWave API automated inventory sync, and hyper-targeted Local SEO for the Ciociaria region.

### 🧪 Test Coverage Status
* **Critical Services Tested**: ✅ ProductStartingPriceService, CategoryQuantityDiscount (Recently completed)

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

* **Outlet Cascade System**: Implemented an advanced configuration modal in the Filament admin panel. Allows admins to assign `is_outlet` status and prices globally to a Product or targeted to specific exposed variants. These settings cascade to update all underlying `ProductSku` permutations.
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
| `/catalogo/{slug}` | `CategoryController` | Category page |
| `/catalogo/{slug}/{product}` | `ProductController` | Product detail page |
| `/cart` | `CartController` | Cart view |
| `/cart/add`, `/cart/update` | `CartController` | Cart mutations |
| `/checkout` | Volt component | Authenticated Checkout form |
| `/dashboard` | Volt component / Controller | Order History & Profile Area |
| `/feed/google-merchant.xml` | `GoogleMerchantFeedController` | Sitemap for Google Shopping |
| `/sitemap.xml` | `SitemapController` | Dynamic sitemap |
| `/outlet` | `OutletProducts` | Outlet products page |

---

## 🧪 Quality Assurance & Test Coverage

The platform runs a Pest PHP test suite featuring **190+ automated unit and architecture assertions**:

* **Functional Coverage**: Suites for new features like `OutletPricingTest`, `OutletPageTest`, `MultiSkuQuantityDiscountTest`, and `ProductStartingPriceServiceTest` ensure the pricing cascade, volume aggregates, and starting price calculations do not regress.
* **Architecture Enforcement**: Strict compliance rules (e.g., `ControllersDoNotSendMailDirectly.php`, `ModelsDoNotDependOnExternalServices.php`).

### 📊 Current Status

* **Total Models**: 20
* **Models with Tests**: ~9 (45%)
* **Total Services**: 23
* **Services with Tests**: ~6 (26%)

### ✅ Tests Already Created

| Test Name | Status | File Path |
| --- | --- | --- |
| ProductPriceCalculatorTest | ✅ Exists | tests/Unit/ProductPriceCalculatorTest.php |
| ProductPricingServiceTest | ✅ Exists | tests/Unit/ProductPricingServiceTest.php |
| QuantityDiscountServiceTest | ✅ Exists | tests/Unit/QuantityDiscountServiceTest.php |
| PricingTierTest | ✅ Exists | tests/Unit/PricingTierTest.php |
| ProductSkuTest | ✅ Exists | tests/Unit/ProductSkuTest.php |
| ProductStartingPriceServiceTest | ✅ Exists | tests/Unit/ProductStartingPriceServiceTest.php |
| CategoryQuantityDiscountTest | ✅ Exists | tests/Unit/CategoryQuantityDiscountTest.php |

### ❌ Missing Tests (High Priority Focus)

The most critical missing tests to implement next:

1. `UserAnonymizationServiceTest` (Once the GDPR deletion strategy is built)

---

## ⚠️ Tests NOT Yet Created — DO NOT SKIP

The following test suites are **required** for production compliance and must be created before deployment:

### Domain Services (Critical)
- `ProductGalleryServiceTest` - Image processing actions decoupled from Product model
- `ProductSynchronizerMetadataTest` - NewWave API metadata sync
- `ProductSynchronizerImagesTest` - Remote image sync
- `ProductSynchronizerSKUVariationsTest` - SKU/variation sync
- `ProductSynchronizerAvailabilityTest` - Real-time availability sync

### Feature Tests (Critical)
- `CheckoutProcessTest` - Authenticated checkout flow
- `CartMutationTest` - Cart add/update operations
- `OrderCreationTest` - Order placement with Stripe webhooks
- `B2BQuoteWorkflowTest` - Private corporate quotation process
- `OutletPricingCascadeTest` - Outlet price cascade to SKUs
- `ShippingTierCalculationTest` - Shipping cost calculation by order total

### Architecture Enforcement Tests (Critical)
- `ControllersDoNotSendMailDirectlyTest` - Ensures controllers delegate to services
- `ModelsDoNotDependOnExternalServicesTest` - Ensures models don't call Stripe/Mail directly
- `NoRawQueriesInControllersTest` - Enforces Eloquent relationships over raw SQL

### Integration Tests (Critical)
- `StripeWebhookHandlerTest` - Payment intent completion handling
- `NewWaveInventorySyncTest` - GraphQL inventory balance polling
- `SpatieMediaLibraryUploadTest` - File upload and processing pipeline
- `CookieBannerConsentTest` - GDPR consent management flow

### Performance Tests (Recommended)
- `ProductCatalogLoadTimeTest` - Homepage/product page load benchmarks
- `CartCheckoutFlowTest` - End-to-end checkout performance
- `DatabaseQueryPerformanceTest` - N+1 query detection and optimization

**Note**: These tests ensure architectural integrity, GDPR compliance, and production reliability. Do not skip creation of any test in this list.

## 🛠️ Development Workflow & Tooling

1. **Environment**: Windows 11 Pro / PowerShell.
2. **Quality Tools**: Pint (PSR-12), Rector (Refactoring), Larastan (Static Analysis). Run via `composer run format`. A blade analyser Sheath by Forte. Run via `php artisan sheath:lint`.
3. **Git Workflow**: Pass tests ➔ Format Code ➔ Stage ➔ Commit ➔ Push to `master`. Pushing to the `production` branch automatically triggers the GitHub Action deployment pipeline.