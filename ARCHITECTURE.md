# Pubblicitta24 — Architecture

This document describes the product domain and the shape of the code. For
current implementation status and next work, see
[PIANO_IMPLEMENTAZIONE.md](./PIANO_IMPLEMENTAZIONE.md). Repository-wide agent
workflow is in [AGENTS.md](./AGENTS.md); path-specific rules are indexed by
[.ai/rules/index.md](./.ai/rules/index.md).

When this document and the repository disagree, the implementation,
migrations, configuration and tests are authoritative.

## 1. Product and customer workflows

Pubblicitta24 is a catalogue and ordering platform for custom-printed apparel,
signage and related products. Products can have different configuration and
pricing models; this is not a quote-only storefront.

- Customers browse the catalogue, configure a product and add it to a
  session-backed cart.
- Checkout requires authentication. A customer can start Stripe Checkout or
  submit a quotation request.
- Both journeys create an `Order` with `OrderItem` records. A quotation is
  distinguished by `payment_status = quotation`; there is no separate quote
  table.
- Admins manage the catalogue, campaigns, quotations/orders and fulfilment
  through Filament.

## 2. Domain model

### Products, variations and SKUs

- `Product::type` describes origin (`standard` or `newwave`); it is distinct
  from `product_class`, which describes the product's shape and pricing
  behavior.
- `ProductClass` distinguishes apparel, item-based products and area-based
  products. Product class informs the pricing model.
- `VariationType` and `VariationOption` describe configurable dimensions and
  their values. Options may have flat or percentage price modifiers and may
  carry images.
- `ProductVariationType` and `ProductVariationOption` connect those options to
  a product.
- `ProductSku` represents a concrete purchasable variant, including price,
  stock and availability.
- `Brand` is a first-class record; `products.brand_id` is nullable. NewWave
  metadata can resolve and synchronize the brand.

### Pricing

Prices are calculated from product configuration, quantity and campaigns;
there is no single stored price that is authoritative for every purchase.

- `ProductPricingService` resolves unit prices. For standard products the
  precedence is SKU override, active campaign offer, applicable pricing tier,
  then base price.
- Category quantity discounts apply along the category ancestry to SKU
  overrides, active offers and base prices. Explicit tier prices take
  precedence over those discounts.
- Outlet SKU overrides are effective only while their assigned campaign is
  active.
- `ProductPriceCalculator` calculates totals and branches to area-based
  pricing where appropriate. Area-based totals use billed area derived from
  requested dimensions and sheet calculations.
- `ProductStartingPriceService` maintains the cached starting-price fields.
  Verify the writer and consumers before relying on any other cached-price
  column.

The implementation is in `app/Services/ProductPricingService`,
`ProductPriceCalculator`, `QuantityDiscountService` and related product
concerns. Pricing changes must be tested at tier, discount and quantity
boundaries and must preserve server-side recalculation.

### Campaigns

`Campaign` has optional start/end dates and separate product-offer and outlet
SKU assignments. A configured offer or outlet SKU is inert unless assigned to
a currently active campaign. Scheduling a campaign does not change a
product's `is_active` flag. Assignments remain until removed, even after a
campaign ends.

A product offer or outlet SKU belongs to one campaign at a time.
`CampaignProductAssociationService` maintains those associations; database
unique constraints also enforce the single-campaign invariant.

### Categories and collections

These are separate concepts:

- **Category** is a hierarchy used for catalogue browsing and category-based
  quantity discounts.
- **Collection** is a curated kit or bundle. Products relate through
  `collection_product`, whose `sort_order` controls their order in a
  collection; a product may belong to multiple collections.

The product page can show other products from its collections. The index
excludes the current product and inactive products/collections, groups results
when necessary, and currently caps each group at eight products.

### Orders and status

Payment and fulfilment are independent axes:

- `PaymentStatus` tracks pending, paid, cancelled or quotation.
- `WorkStatus` tracks order fulfilment, including awaiting customer files,
  processing, ready, shipped and completed.

`orders` and `order_items` retain customer configuration and pricing.
`order_items.customization_json` stores the configured cart item;
`design_file_path` exists for production-file tracking. Customer-facing artwork
upload is tracked as remaining work in the implementation plan.

Shipping supports delivery and pickup; delivery rates come from shipping tiers.
Addresses belong to user accounts.

## 3. Application boundaries

```text
routes/
  HTTP entry points and middleware
app/Http/Controllers, app/Http/Requests/
  Translate and validate HTTP input; coordinate a focused use case
app/Livewire/, resources/views/
  Interactive and rendered customer-facing UI
app/Services/
  Pricing, cart, checkout, campaigns, integrations, synchronization and feeds
app/Support/
  Value objects shared across application services
app/Models/, app/Concerns/
  Eloquent entities, relationships and focused product behavior
app/Jobs/
  Queued synchronization and image work
app/Observers/
  Cache invalidation, media cleanup and model lifecycle side effects
app/Filament/
  Admin panel resources and workflows
app/Enums/
  Domain states and classifications
```

### Key workflows and code

| Concern | Main implementation |
| --- | --- |
| Pricing | `ProductPricingService`, `ProductPriceCalculator`, `QuantityDiscountService` |
| Cart | `CartManager`, `CartPresenter`, `CartPresentationDataLoader` |
| Checkout and orders | `CheckoutOrderService`, `StripeCheckoutSessionService`, `CheckoutOrderDetails` |
| Stripe webhooks | `WebhookController`, `OrderPaymentNotificationService` |
| NewWave GraphQL | `NwgApiClient`, `ProductSynchronizer`, aspect-specific synchronizers, `SyncNewWaveProductJob` |
| Campaign assignment | `CampaignProductAssociationService`, `HasProductCampaigns` |
| Catalogue search | `ProductSearchService` and Laravel Scout |
| Product collections | `ProductCollectionIndexService` and shared product-card views |
| Media and galleries | `ProductGalleryService`, `ProductMediaSyncService`, `ProductImageSynchronizer` |
| Google Merchant feed | `GoogleMerchantFeedBuilder`, `MerchantFeedItemWriter`, `GoogleMerchantFeedCache` |

### Customer and admin surfaces

- `routes/web.php` defines catalogue, search, collection, portfolio, cart,
  checkout, dashboard, feed and sitemap routes.
- Checkout UI is a Livewire component; the outlet listing is class-based
  Livewire. Volt single-file components live in `resources/views` and use
  `Route::livewire(...)` where routed directly.
- `/admin` is the Filament panel. Some product-management endpoints share the
  `/admin` URL prefix but are ordinary controllers; inspect routes rather than
  assuming every such endpoint is a Filament page.
- Stripe webhooks are handled separately from browser checkout. The webhook
  route is CSRF-exempt in `bootstrap/app.php`.

## 4. Integrations and synchronization

### NewWave

NewWave is a third-party GraphQL product source. `SyncNewWaveProductJob`
supports the `SyncLevel` modes:

| Level | Scope |
| --- | --- |
| `Full` | Metadata, images, variants, prices and availability |
| `Meta` | Product metadata and specifications |
| `Availability` | SKU quantities and availability |

Sync state is tracked on the product (`sync_status`, `sync_progress`,
`synced_at`). There is no scheduled full-catalogue sync; admins dispatch jobs,
and stale product detail requests may defer an availability refresh. External
credentials and endpoint configuration come from the environment.

### Search, media and merchant feed

- Product search uses Laravel Scout through `ProductSearchService`, with
  catalogue filters applied by the application.
- Product and category media use Spatie Media Library alongside NewWave
  remote imagery where applicable.
- The Google Merchant feed is rendered and cached by dedicated services.
  Product/SKU observers invalidate relevant cache. Keep relation access in
  feed loops bounded; avoid introducing per-product queries.

## 5. Conventions and important gotchas

- **Pricing and campaign rules are domain behavior.** Change the shared
  services rather than duplicating price calculations in a controller, view or
  Filament form.
- **Search eager-loading closures:** Scout's `query()` callback receives an
  Eloquent builder. Do not type-hint it as `Laravel\Scout\Builder`.
- **Eloquent eager-load constraints:** Larastan expects the broad `Relation`
  callback type. Follow existing code and do not narrow it to a particular
  relation class.
- **Theme:** `resources/css/app.css` defines theme-aware tokens and dark-mode
  inversion. Use those tokens for surfaces and text; avoid hardcoded white or
  black colors that do not adapt to the selected theme.
- **Brand relationship:** the Product relation is named `brand_relation` and
  explicitly uses `brand_id`. Check for accessor/API implications before
  renaming it.
- **Authentication:** checkout requires authentication but not email
  verification; dashboard access has stricter verification requirements.
  Confirm current middleware in routes before changing access behavior.
- **Static analysis:** fix PHPStan findings at their source. Any suppression
  must explain its reason, following the existing project rule.
- **Schema:** checked-in local database artifacts can lag migrations. For
  schema questions, inspect migrations or use
  `php artisan truss:export --format=llm --compact` against the intended
  database; do not infer production schema from a local SQLite file.

## 6. Stack reference

The project uses PHP 8.4+, Laravel 13, Filament 5, Livewire 4, Volt 1,
Flux 2, Tailwind CSS 4 and Vite 8. Other key packages include Fortify,
Scout, Spatie Media Library and Stripe PHP. Exact resolved versions are
recorded in `composer.lock` and `package-lock.json`; verify them there before
relying on a version-specific API.

| Area | Package | Resolved version recorded in this snapshot |
| --- | --- | --- |
| Framework | `laravel/framework` | 13.40.0 |
| Admin panel | `filament/filament` | 5.11.1 |
| Reactive UI | `livewire/livewire` | 4.4.8 |
| Single-file components | `livewire/volt` | 1.11.2 |
| UI kit | `livewire/flux` | 2.21.0 |
| Search | `laravel/scout` | 11.9.0 |
| Media | `spatie/laravel-medialibrary` | 11.23.11 |
| Payments | `stripe/stripe-php` | 20.3.2 |
| Authentication | `laravel/fortify` | 1.41.0 |
| Tests | `pestphp/pest` | 5.3.5 |
| Static analysis | `larastan/larastan` | 3.10.0 |
| Formatting/refactoring | `laravel/pint`, `rector/rector` | 1.32.1, 2.8.4 |
