# 📋 Implementation Plan - Pubblicittà24 E-commerce & Local SEO

**Status**: ✅ COMPLETED (Ready for Go-Live & Ongoing Optimization)  
**Focus**: Complete E-commerce with Online Payments, Private Quotes & Local SEO (Fiuggi and surroundings)  
**Last updated**: July 24, 2026

---

## 📊 Project Overview

**Name**: Pubblicittà24 E-commerce platform for custom prints, standard products (Business Cards, Large Format Printing, Forex, Banners, Flyers), and promotional/workwear apparel.  
**Focus**: Complete E-commerce with Online Payments (Stripe) and Private Quotes.  
**Geographic Target**: Fiuggi, province of Frosinone, and neighboring municipalities (Anagni, Alatri, Ferentino, Sora, Paliano, Acuto, Piglio, Guarcino, etc.).  
**Flow**: Cart Checkout → Stripe Payment **or** Request Private Quote → Order Management.  
**Tech Stack**: Laravel 13, Livewire 4, Filament 5, Volt 1, Tailwind CSS 4, Spatie Media Library 11, Laravel Scout 11.

---

## 🗄️ Database Structure (Unified & Clean)

The database is optimized with unified migrations, featuring advanced management of variants, pricing tiers, shipping brackets, and custom print jobs.

### Main Tables

```
📦 products
├── id, name, slug, sku, description, category_id, is_featured
├── type (standard|newwave), pricing_model (fixed|quantity|area), min_area
├── max_width, max_height, sheet_width, sheet_height, allows_custom_size
├── min_custom_width, max_custom_width, min_custom_height, max_custom_height
├── sync_status, sync_progress, synced_at, is_active
├── override_price, override_description, remote_images (JSON)
├── price, offer_price, created_at, updated_at

📦 categories
├── id, name, slug, parent_id, description, is_active, display_mode

📦 media (Spatie Media Library)
├── id, model_type, model_id, file_name, mime_type, custom_properties, responsive_images, etc.

📦 pricing_tiers (Price and quantity matrix)
├── id, product_id, product_sku_id, is_custom_price, min_quantity, max_quantity, price_per_unit

📦 category_quantity_discounts (Quantity discounts per category)
├── id, category_id, min_quantity, max_quantity
├── discount_type (percent|fixed), discount_value, description

📦 addresses (User Address Management)
├── id, user_id (FK), type (shipping|billing), name, street, city, state, zip, country, phone
├── vat_number, fiscal_code, sdi_code, pec_email, is_default

📦 orders (Ecommerce Orders)
├── id, user_id (FK), order_number, payment_status, work_status
├── total_price, total_items, shipping_cost, shipping_method, shipping_address_id, billing_address_id
├── stripe_session_id, stripe_payment_intent_id, paid_at, notes
├── payment_status: pending | paid | quotation | failed | refunded

📦 order_items (Individual order line items / Jobs)
├── id, order_id, product_id, quantity, unit_price, subtotal
├── customization_json (JSON with selected options)
├── design_file_path (Uploaded file path)
├── work_status

📦 shipping_tiers (Shipping brackets)
├── id, name, min_order_total, cost, is_active
```

---

## 🎯 Implemented Features

### ✅ WEEKS 1-2: Foundations & MVP (COMPLETED)

- [x] **Core Backend**: Migrations, Models, Relationships, and Seeders.
- [x] **Catalog**: Product listing, category view, and product detail page.
- [x] **Basic Cart**: Add to cart logic, dynamic price calculation, and quantity discounts.
- [x] **Admin Panel**: Filament resources for products, categories, and discounts.

---

### 🚀 WEEKS 3-4: Advanced Sync & Gallery Management (COMPLETED)

#### 🌐 Advanced NewWave API Integration

- [x] **Authenticated GraphQL**: Secure integration with NewWave gateway.
- [x] **Lazy-Sync**: Automatic data freshness verification every 12 hours.
- [x] **Fast Availability Sync**: Rapid stock/inventory updates without re-importing full products.
- [x] **CDN-Mode**: Automated synchronization of remote images.

#### 🖼️ Media & Gallery Management

- [x] **Hybrid Gallery Engine**: Unified local images (Spatie MediaLibrary) and remote images (`images` table).
- [x] **Smart Color Filtering**: Gallery displays only images associated with the selected color.
- [x] **Admin Gallery Control**: Interface for manual reordering and color association overrides for API images.

#### 🛒 Job-Based Cart & Standard Products

- [x] **Job UUID**: Each cart item addition represents a distinct job with a unique UUID.
- [x] **Standard Product Configurator**: Livewire forms for area-based or quantity-based products (Forex, Business Cards, Banners with `itemsPerSheet` calculation).

---

### 🧾 Module 3: Payments & Quotes (COMPLETED)

- [x] **Stripe Checkout**: Direct checkout with Stripe redirect and Webhook handling.
- [x] **Private Quote Request**: Button and workflow to request custom private quotes directly from the cart.

---

### 🔍 Module 4: SEO, Feed & Social Sharing v2.0 (COMPLETED - July 2026)

- [x] **Meta Tags & Social Open Graph**: Dynamic meta tags (`og:title`, `og:description`, `og:image`, `og:url`, `twitter:*`) across products, categories, and homepage.
- [x] **Variant Preview Handling in OG & Canonical**: Automatic detection of query-string exposed variants (e.g. `?colore=96`), dynamically switching Open Graph image, canonical URL, and Schema.org structured data.
- [x] **Google Merchant XML Feed**: Automated generator at `/feed/google-merchant.xml` for Google Shopping with variant details (color, size, specific image, link with query params).
- [x] **XML Sitemap**: Dynamic sitemap at `/sitemap.xml` for active products, categories, and institutional pages.
- [x] **Laravel Scout v11**: Full-text search integration for the product catalog.

---

## 📍 Local SEO Strategy (Fiuggi and Surroundings)

To dominate local search results in **Fiuggi and neighboring municipalities** (Anagni, Alatri, Ferentino, Sora, Frosinone, Paliano, Acuto, Piglio, Guarcino, Subiaco), covering **all print and visual communication products** in addition to apparel:

### 1. Geolocated Titles & Meta Tags (Global & Categories)

- **Homepage Title**: `Pubblicittà24 | Stampa Digitale, Grande Formato e Abbigliamento a Fiuggi`
- **Homepage Description**: `Stampa digitale professionale a Fiuggi e provincia di Frosinone: biglietti da visita, volantini, striscioni, pannelli Forex, gadget e abbigliamento personalizzato. Preventivi gratuiti online.`
- **Category Pages**:
    - _Business Cards_: "Stampa Biglietti da Visita a Fiuggi e Dintorni | Pubblicittà24"
    - _Large Format & Rigid Panels_: "Stampa Grande Formato, Forex e Striscioni Fiuggi | Pubblicittà24"
    - _Flyers & Folded Leaflets_: "Stampa Volantini e Pieghevoli a Fiuggi e Frosinone | Pubblicittà24"
    - _Workwear_: "Abbigliamento da Lavoro Personalizzato Fiuggi e Ciociaria | Pubblicittà24"

### 2. Schema.org LocalBusiness / PrintShop Structured Data

Integration of `LocalBusiness` / `PrintShop` schema inside `resources/views/layouts/layout.blade.php`:

- **Name**: Pubblicittà24
- **Address**: Fiuggi (FR), Italy
- **Served Area (`areaServed`)**: `["Fiuggi", "Anagni", "Alatri", "Ferentino", "Frosinone", "Sora", "Paliano", "Acuto", "Piglio", "Guarcino", "Ciociaria"]`
- **Offered Services**: Digital Printing, Business Cards, Large Format Printing, Signs, Rigid Panels, Promotional and Workwear Apparel.

### 3. Dedicated Local Landing Pages ("Services by Area")

Creation of dedicated landing pages targeting high-intent local queries:

- `/stampa-digitale-fiuggi`: Digital print for business cards, brochures, flyers, and catalogs for businesses and events in Fiuggi and province.
- `/stampa-grande-formato-fiuggi`: PVC banners, mesh banners, Forex/Plexiglas panels, roll-ups, and trade show displays in Ciociaria.
- `/abbigliamento-lavoro-fiuggi`: Work clothes and uniforms for hotels, restaurants, spas, and retail businesses in Fiuggi.

### 4. Geolocated Footer & About Us

- Footer section: _"Servizio di Stampa e Personalizzazione a Fiuggi e in Provincia di Frosinone"_, listing key served municipalities (with fast delivery or in-store pickup).

### 5. Google Business Profile Integration (Google Maps)

- Synchronization of the Pubblicittà24 Google Business Profile with the website.
- Direct links for customer reviews and maps to strengthen presence in Google's **Local Pack** for "near me" searches.

---

## 📅 Updated Milestones

```
JULY 2026 (Current Status: Completed & Local SEO Active)
├─ ✅ Open Graph & Meta Tags v2.0 (og:image, og:url, twitter cards)
├─ ✅ Variant Resolution Exposed in Social Shares (Specific image for ?colore=XX)
├─ ✅ Google Merchant XML Feed v1.0 (/feed/google-merchant.xml)
├─ ✅ Dynamic XML Sitemap (/sitemap.xml)
├─ ✅ Laravel Scout v11 Integration (Database full-text search)
├─ ✅ Expanded Test Suite (190+ passing Pest/PHPUnit tests)
├─ ✅ Schema.org LocalBusiness / PrintShop Implementation (real opening hours + areaServed Italy and Rome-Naples)
├─ ✅ Geolocated Meta Tags & Titles (Fiuggi, Frosinone, Rome-Naples, and Italy)
├─ ✅ Geolocated Footer with served areas and map
└─ ⏳ Creation of Dedicated Local Landing Pages (e.g., /stampa-grande-formato-fiuggi)
```

---

## 🧪 Test Suite

Over **180+ tests** (Pest/PHPUnit) successfully passing, covering:

- `CartTest`, `SearchTest`, `ProductPageTest`, `OrderTest`, `QuantityDiscountServiceTest`.
- Tests for Open Graph, variant preview meta tags, XML Sitemap, and Google Merchant Feed.
- Custom format and scaling calculation tests (`StandardProductResourceTest`).
- Private quote request flow and shipping method checkout tests (`CheckoutTest`).

---

## 🚀 TARGET LIVE & LOCAL GROWTH: Ready for Release and Local Positioning!

## Errors Noticed

### TODO

[ ] In /cart page if for example i have basic t orange 2 Ms and 7 XLs ofcourse it doesn't calculate the discount (triggered at 10) but if I up the XLs to 8 it still does't trigger the discount, if instead i was in the product page and isert the same quantities 2 Ms and 8 XLs it shows me the discount

## Optimize For Laravel Best Practices, CRUDdy by Design and SOLID Best Practices

### TODO

[x] **Extract `ProductGalleryService`**  
Move image aggregation methods out of `Product`: - `getAllImages()` - `getImagesForOption()` - `getFirstImage()` - `getFirstImageUrl()`

[x] **Split `ProductSynchronizer`**  
Separate: - Metadata synchronization - Image synchronization - SKU/variation synchronization - Availability synchronization

[x] **Reduce N+1 queries in cart rendering**
`CartController::index()` still performs product pricing and variation queries while enriching each cart item. This should move into a dedicated cart presenter/query service with all required data preloaded.

[x] **Move email side effects out of `Order`**
Model events and `completePayment()` send emails directly. Since queues are unavailable, use container resolution (via `app()`) after database commits to keep behavior synchronous but reduce response blocking by keeping domain model clean. Created `OrderNotificationService` and `OrderPaymentNotificationService` to handle all email notifications.  
Model events and `completePayment()` send emails directly. Since queues are unavailable, use `defer()` after database commits to keep behavior synchronous but reduce response blocking.

[ ] **Add missing indexes after confirming query plans**  
Candidate indexes: - `images(product_id, variation_option_id)` - `orders(user_id, created_at)` - `product_skus(product_id, sku)` - `products(category_id, is_active)`

[ ] **Replace remaining `app()` service resolution**  
Constructor injection would improve testability in `ProductAvailabilityService`, `ProductSynchronizer`, and the remaining `Product` compatibility wrappers.

[ ] **Add order status enums**  
Replace string statuses such as `pending`, `paid`, `quotation`, and `processing` with `PaymentStatus` and `WorkStatus` enums.

[ ] **Add architecture tests**  
Enforce that: - Controllers do not send mail directly. - Models do not depend on Stripe or mail. - External API calls live in services. - Policies protect admin and user-owned resources.
