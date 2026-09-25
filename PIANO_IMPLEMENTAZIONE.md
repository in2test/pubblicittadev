# 📋 Implementation Plan & System Architecture — Pubblicittà24

**Current Status**: 🚀 PRODUCTION READY (Completed & Local SEO Active)  
**System Date**: September 18, 2026  
**Core Scope**: E-commerce platform with automated Stripe payments, B2B manual quotation flows, NewWave API automated inventory sync, and hyper-targeted Local SEO for the Ciociaria region.

---

## 🚨 ACTIVE ISSUES & OPEN TICKETS

### ✅ [DONE] Outlet System with Product & Exposed Variations Cascade
*   **Goal**: Provide an administrative Outlet section showing Products where admins can configure outlet status and override prices either for the entire product or per exposed variation (e.g. `Colore: Bianco` with all its sub-sizes S, M, L... receiving the outlet price, leaving other variants like `Giallo` at regular price).
*   **Outcome**:
    *   Refactored `OutletSkuResource` to manage **Products** with a dedicated modal action ("Configura Outlet").
    *   Modal enables assigning `is_outlet` and `outlet_price` at the whole-product level or targeting individual exposed variants (`expose_in_url` / non-modifier variation types), with automatic cascade down to all matching `ProductSku` records.
    *   Enhanced frontend configurator (`cart-form.blade.php`, `info.blade.php`, `card.blade.php`) to display visual Outlet indicators directly on color swatches and variant buttons, with dynamic badge and pricing on selection.
    *   Covered with automated unit & feature tests (`OutletPricingTest`, `OutletPageTest`).

### ✅ [DONE] Multi-SKU Quantity Tier Discount Application & Price Calculation
*   **Symptom**: For multi-SKU products (e.g. NewWave apparel with multiple sizes selected), the badge correctly highlighted volume discounts based on `$totalQuantity`, but the calculated unit price passed per-SKU quantity (`$skuQty`) to `calculateFinalUnitPrice()`, causing full prices or incorrect tiers to be applied unless a single size reached the threshold alone.
*   **Solution**: In `app/Services/ProductPriceCalculator.php` (`calculateTotalPrice()`), updated `calculateFinalUnitPrice()` call to evaluate volume discount tiers using `$totalQuantity` (aggregate quantity across all variants) while multiplying by `$skuQty` for the line subtotal. Verified with automated test suites.

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
