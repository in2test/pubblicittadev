# Pubblicitta24 — Architecture & Scope

**Orientation document for coding agents.** Read this first, then the file it
points you to for the task at hand.

`PIANO_IMPLEMENTAZIONE.md` is the companion document: it holds the project
*status*, the release model, and the prioritised backlog. This file holds the
*shape of the code* — what the domain means, where each concern lives, and
which conventions the tooling will reject you for breaking.

**Trust the code over either document.** When they disagree, the code, the
migrations, and the current test results win. Update this file when the
architecture moves.

---

## 1. What this application is

Pubblicitta24 is a **catalogue and ordering platform for custom-printed
apparel and signage** (Italian business, Fiuggi/Ciociaria area, customers
mainly in the Roma–Napoli corridor). It is **not** a quote-only storefront and
**not** a generic e-commerce template: the interesting part of the domain is
that a single product can be priced by area, by tier, by SKU, by category
discount, and by campaign offer, all at once.

Two customer journeys exist side by side:

- **Buy now** — authenticated → Stripe Checkout Session → webhook marks paid.
- **Request a quotation** — authenticated → order stored with
  `payment_status = quotation` → an admin revises line prices by hand and the
  customer is contacted out of band.

Both journeys write into the same `orders` / `order_items` tables. There is no
separate `quotes` table.

---

## 2. Stack

Versions are the ones actually resolved in `composer.lock` / `package.json`.

| Area | Package | Version |
| --- | --- | --- |
| Language | PHP | `^8.4` (composer.json requires `>= 8.4.1`; CI tests 8.4 and 8.5) |
| Framework | `laravel/framework` | v13.37.0 |
| Admin panel | `filament/filament` | v5.10.2 |
| Reactive UI | `livewire/livewire` | v4.4.7 |
| Single-file components | `livewire/volt` | v1.11.2 |
| UI kit | `livewire/flux` | v2.20.1 |
| Search | `laravel/scout` | v11.9.0 — installed and configured but **unused by the app, see §5** |
| Media | `spatie/laravel-medialibrary` | 11.23.10 |
| Payments | `stripe/stripe-php` | v20.3.1 |
| Auth | `laravel/fortify` | v1.41.0 |
| Tests | `pestphp/pest` | v5.3.3 |
| Static analysis | `larastan/larastan` | 3.10.0 |
| Formatting | `laravel/pint` | v1.32.1 · `rector/rector` 2.7.0 |
| CSS / build | Tailwind CSS 4 · Vite 8 | `@tailwindcss/vite` |

Single-file components are routed with **`Route::livewire(...)`**, not the
older `Volt::route(...)` helper — the project was upgraded to Volt/Livewire 4.

---

## 3. Domain vocabulary

This is the part that is genuinely hard to guess from the code. Learn these
before touching pricing or campaigns.

### Product identity and shape

| Term | Meaning |
| --- | --- |
| `Product` | A configurable item in the catalogue. Has a `product_class` and a `pricing_model`. |
| `Product::TYPE_STANDARD` / `TYPE_NEWWAVE` | The `type` column: where the product came from (`standard` vs `newwave`). Distinct from `product_class` — origin, not shape. |
| `ProductClass` enum | `Apparel`, `AreaBased` (print on a sheet/surface), `ItemBased` (standard per-piece). The `product_class` column: what the product *is*, which decides how it is priced and sized. The Filament form derives `pricing_model` from it — `AreaBased → 'area'`, `ItemBased → 'quantity'`, `Apparel → 'fixed'`. |
| `VariationType` / `VariationOption` | A configurable dimension (e.g. "Taglia", "Colore") and one of its values. Options may carry a **price modifier** (`ModifierType::Flat` or `Percentage`) and may bear an image. |
| `ProductVariationType` / `ProductVariationOption` | The join of a product to a variation type/option — how a given product exposes a dimension. |
| `ProductSku` | A concrete purchasable variant with its own price, stock and availability. Belongs to a product. |
| `Brand` | First-class record, `products.brand_id` (nullable). Name is **synced from the NewWave API**, resolved by `BrandResolver`. |

### Pricing

Prices are computed, never stored as a single truth. Two classes are
involved, and it matters which one you are reading:

- **`ProductPricingService`** resolves the *unit* price.
- **`ProductPriceCalculator`** drives the *total* from
  `calculateTotalPrice()` (area-based products branch off first; everything
  else goes to `calculateStandardProductPrice()`).

The unit price is resolved in this order:

1. **SKU override** — `product_skus.override_price`, if it is not null. It
   applies unconditionally for a normal SKU; for an **outlet** SKU it applies
   only when that SKU `hasActiveCampaign()`. The category quantity discount is
   applied on top.
2. **Campaign offer** — `products.offer_price`, if it is `> 0` **and** the
   product `hasActiveOfferCampaign()`. The category quantity discount is
   applied on top.
3. **Pricing tier** — `pricing_tiers` gives a unit price for a quantity band.
   This price is returned **raw**: the code comment is "Explicit tier price
   takes precedence over quantity-based category discounts", so a tier price
   is *never* discounted. Note also that `getTierPrice()` with a SKU does
   **not** fall back to product-level tiers — a SKU with no SKU-specific tier
   skips the tier entirely and falls through to the base price.
4. **Base price** — `products.price`, with the category quantity discount
   applied.

**Category quantity discounts** (`category_quantity_discounts`) apply down the
whole category path, not just the direct category. They are applied to the
SKU override, the campaign offer and the base price — but **not** to a tier
price.

Area-based products are priced as **price per square metre × total billed
area**, where the billed area comes from the requested width/height and the
sheet calculations (`getSheetsNeeded()`, `calculateTotalBilledArea()`). The
per-square-metre price still comes from
`ProductPricingService::getPriceForAreaBasedProduct()`.

Two price helpers are cached on the product row for read performance:
`cached_starting_price` and `cached_starting_unit_price`, both written by
`ProductStartingPriceService::updateCachedPrices()`. A third column,
`cached_base_price`, **exists in the schema but is never written by any
service** — do not read it expecting a value.

### Campaigns (the "offers" mechanism)

A `Campaign` has a nullable `starts_at` / `ends_at` window. It owns two
many-to-many relations: `campaign_products` (product offers) and
`campaign_product_sku` (outlet SKUs).

**The rule that surprises people:** a product's `offer_price` and an outlet
SKU are *inert* until the product/SKU is **assigned to a campaign** and that
campaign is currently active. Scheduling a campaign never flips a product's
`is_active` flag. An assignment survives its campaign's end date until an
admin removes it.

A product offer and an outlet SKU belong to a **single** campaign at a time.
`CampaignProductAssociationService` replaces the previous association
transactionally, though it only *clears* the previous `campaign_products` row
when the product actually has an `offer_price`; outlet-SKU rows are always
re-synced. The invariant is enforced independently by the database:
`campaign_products` has `primary(['campaign_id','product_id'])` **plus**
`unique('product_id')`, and `campaign_product_sku` has
`primary(['campaign_id','product_sku_id'])` plus `unique('product_sku_id')`.

### Collections vs categories

Easy to confuse, unrelated concepts:

- **Category** — a hierarchical tree (`categories.parent_id`) used for
  browsing and for category-scoped quantity discounts.
- **Collection** — a curated *kit/bundle* ("kit" or "bundle"): name,
  description, banner image, ordering, active/featured flags. Many-to-many
  with products through `collection_product`, a composite-PK pivot carrying
  `sort_order`. A product can belong to several collections.

The product detail page renders an **index of the other products in the same
collection** — flat when the product is in one collection, grouped when it is
in several. Built by `ProductCollectionIndexService`
(`forProduct(): Collection`), rendered by `x-product.collection-index`,
reusing the shared product card and grid.

The index excludes the current product and filters out inactive products and
inactive collections, and it is **already capped at 8 siblings per
collection** (`ProductCollectionIndexService::PRODUCTS_PER_COLLECTION`).
`PIANO_IMPLEMENTAZIONE.md` §7 still lists "cap it to the first N siblings" as
open work — that part is already done. When the collection holds more
siblings than are shown, the component renders "N di M articoli" plus a
"Vedi Tutti gli Articoli della Collezione" link to the collection page.

### Order state

Two independent axes on an order:

- `PaymentStatus` — `Pending`, `Paid`, `Cancelled`, `Quotation`.
- `WorkStatus` — the fulfilment workflow (pending → awaiting customer files →
  processing → ready → shipped → completed). `Order::updateWorkStatusFromItems()`
  takes the **lowest** weight among the order's items (a minimum, not a
  merge) and writes it — but writes the numeric weight rather than the enum
  value, which is the bug flagged in §10.

### NewWave synchronisation

NewWave is the third-party product API (GraphQL). Products carry
`sync_status` (`Pending`/`Syncing`/`Synced`/`Failed`), `sync_progress` and
`synced_at`. Admins trigger syncs at a chosen depth via the `SyncLevel` enum:

| Level | Refreshes |
| --- | --- |
| `Full` | metadata, images, variants, prices, availability |
| `Meta` | name, price, description, technical sheets, certifications, washing instructions |
| `Availability` | SKU quantities and availability only |

**There is no scheduled catalogue sync.** `App\Console\Kernel::schedule()` is
empty ("No automatic scheduling in Phase 1. Use manual dispatch."); product
detail pages only *defer* an availability refresh for NewWave products whose
`updated_at` is at least 12 hours old, guarded by a `Cache::lock` on
`nwg-availability-{productId}` (`ProductController`).

---

## 4. Layer map

```
routes/web.php
   │
   ├── Controllers (app/Http/Controllers)   thin: translate HTTP ⇄ one call
   │      └── Form Requests (app/Http/Requests/Cart)  input contract
   │
   ├── Livewire / Volt components (app/Livewire, resources/views/pages)
   │      └── reactive surfaces: outlet listing, checkout page
   │
   ├── Services (app/Services)   ← all domain logic lives here
   │      ├── Support value objects (app/Support)
   │      └── external APIs, pricing, cart, checkout, feeds, sync
   │
   ├── Models (app/Models)   Eloquent entities + relations
   │      └── Concerns (app/Concerns)   product traits, keeps Product thin
   │
   └── Filament resources (app/Filament/Resources)   the admin panel
```

Jobs (`app/Jobs`) carry queued work: `SyncNewWaveProductJob` (NewWave
synchronisation) and `SyncProductImagesJob` (Media Library conversions, not
NewWave). Enums (`app/Enums`) hold `ModifierType`, `PaymentStatus`,
`ProductClass`, `SyncLevel`, `SyncStatus`, `WorkStatus`.

Observers (`app/Observers`) do **not** touch sync state — `sync_status`,
`sync_progress` and `synced_at` are written by `SyncNewWaveProductJob` and
the NewWave Filament pages. What the observers actually do: invalidate the
cached starting prices (`ProductObserver`, `ProductSkuObserver`,
`PricingTierObserver`), invalidate the Google Merchant feed cache
(`ProductObserver`, `ProductSkuObserver`), clean up media (`MediaObserver`),
and send notifications (`UserObserver`, `NewsletterSubscriptionObserver`).

`app/Http/Middleware/AuthenticateAdmin.php` is the only custom middleware;
CSRF is exempted for `webhooks/stripe` in `bootstrap/app.php`.

### Where things live

| Concern | Location |
| --- | --- |
| Pricing math | `app/Services/ProductPriceCalculator`, `ProductPricingService`, `QuantityDiscountService` |
| Cart state | `app/Services/CartManager` (session-backed, key `cart_items`), `CartItemPresenter`, `CartPresenter`, `CartPresentationDataLoader` |
| Checkout / orders | `app/Services/CheckoutOrderService`, `StripeCheckoutSessionService`; `app/Support/CheckoutOrderDetails` |
| Stripe webhooks | `app/Http/Controllers/WebhookController` → `OrderPaymentNotificationService` |
| NewWave integration | `app/Services/NwgApiClient` (GraphQL), `ProductSynchronizer` + per-aspect synchronisers, `ProductDataMapper`; jobs in `app/Jobs` |
| Brand resolution | `app/Services/BrandResolver` |
| Campaign assignment | `app/Services/CampaignProductAssociationService`; `app/Concerns/HasProductCampaigns` |
| Search | `app/Services/ProductSearchService` |
| Google Merchant feed | `GoogleMerchantFeedBuilder`, `GoogleMerchantFeedCache`, `MerchantFeedItemWriter`, `app/Support/MerchantFeedItem` |
| Collection index | `app/Services/ProductCollectionIndexService` |
| Media / gallery | `app/Services/ProductGalleryService`, `ProductMediaSyncService`, `ProductImageSynchronizer` |

### Directory map

| Path | Contents |
| --- | --- |
| `app/Http/Controllers` | HTTP coordination only |
| `app/Http/Requests/Cart` | Form requests for cart add/update/price |
| `app/Models` | 21 Eloquent entities |
| `app/Concerns` | `HasProductCampaigns`, `HasProductExtendedDetails`, `HasProductGallery`, `HasProductOutletPricing`, `HasProductPresentation`, `HasProductPricing`, `HasProductVariationDisplay` |
| `app/Services` | 36 services — the domain layer |
| `app/Support` | `CheckoutOrderDetails`, `MerchantFeedItem`, `NewWaveProductAttributes`, `SlugGenerator` |
| `app/Jobs` | `SyncNewWaveProductJob`, `SyncProductImagesJob` |
| `app/Observers` | `ProductObserver`, `ProductSkuObserver`, `PricingTierObserver`, `MediaObserver`, `NewsletterSubscriptionObserver`, `UserObserver` — keep cached prices and sync state in step with their sources |
| `app/Policies` | `OrderPolicy`, `ProductPolicy` |
| `app/Mail` | Order and newsletter notifications (`OrderPaidConfirmation`, `OrderPlacedNotification`, `OrderStatusChangedNotification`, `AdminOrderPaidNotification`, `NewsletterSubscribedAdminNotification`, `UserCreatedAdminNotification`) |
| `app/Console/Commands` | Maintenance commands: `RebuildGoogleMerchantFeed`, `UpdateCachedProductPrices`, `SyncProductQuantities`, `SyncProductImagesCommand`, `MarkOutletSkus`, `MigrateImagesToMediaLibrary`, `ImportSqliteData`, plus the seeder helpers `ModifySeeder` and `UpdateSeederTemp` |
| `app/Actions/Fortify` | `CreateNewUser`, `ResetUserPassword` |
| `app/Filament/Resources` | 15 admin resources, each a directory holding the Resource class plus a *subset* of `Schemas/`, `Tables/`, `Pages/` (not uniform — `CategoryQuantityDiscounts` has no `Tables/`, `NewsletterSubscriptions` and `Users` have only `Pages/`, `OutletSkuResource` has no `Schemas/`, `Campaigns` and `Products` add `RelationManagers/`): `BrandResource`, `CampaignResource`, `CategoryResource`, `CategoryQuantityDiscountResource`, `CollectionResource`, `NewsletterSubscriptionResource`, `OrderResource`, `OutletSkuResource`, `PortfolioItemResource`, `ProductResource`, `NewWaveProductResource` (nested under Products), `ShippingTierResource`, `TransporterResource`, `UserResource`, `VariationTypeResource` |
| `app/Livewire` | `OutletProducts` (class-based) + `app/Livewire/Actions` |
| `resources/views` | Blade, Livewire, Volt. **Volt single-file components are prefixed with `⚡`** (`components/⚡product.blade.php`, `pages/settings/⚡profile.blade.php`) — that prefix is the Volt marker, and those files hold both the PHP and the Blade. Class-based Livewire lives in `app/Livewire` (`OutletProducts`) instead. `components/product/` holds the shared product card, grid, gallery, collection index |
| `tests/` | `Architecture`, `Feature` (+ `Auth`, `Console`, `Filament`, `Settings`), `Unit` (+ `Models`, `Services`), `Support` |

---

## 5. Things that will bite you

**Search is not Scout.** `Product` still imports `Laravel\Scout\Searchable` and
implements `toSearchableArray()`, and `config/scout.php` is still configured
(`driver => database`), but nothing calls `::search()` anywhere in `app/`.
`ProductSearchService` runs a plain Eloquent query that ANDs the search
words across `name`, `sku`, `description`, `category.name` and
`brand_relation.name`. The header search box and the catalogue page both go
through this one service, so they cannot drift apart — `ProductSearchParityTest`
guards exactly that. Treat the Scout trait as dead weight, not as the search
implementation.

**`brand_relation`, not `brand`.** The `Product` relation to `Brand` is named
`brand_relation` and declares its foreign key explicitly
(`belongsTo(Brand::class, 'brand_id')`) because the relation name no longer
matches the column — without the explicit key Laravel would guess
`brand_relation_id`. The name is a leftover: a `brand` **accessor** used to
occupy that name and was removed in commit `32597c6`, but the relation was
never renamed. Use `brand_relation` as-is; do not "fix" it to `brand` without
checking for the accessor returning.

**Checkout requires authentication but not email verification.** It is grouped
under `middleware(['auth'])`, not `['auth', 'verified']` — unlike `/dashboard`.

**`/webhooks/stripe` is CSRF-exempt** (configured in `bootstrap/app.php`).

**Do not narrow eager-load closure types.** Larastan types the closure passed
to `with()` as `Closure(Relation): mixed`. Writing `fn (BelongsToMany $q) => ...`
is a static-analysis error even when the relation really is a `BelongsToMany`
and the code works at runtime. Use the untyped `fn ($query) => ...` form the
rest of the codebase uses.

**Fix PHPStan findings at the cause.** There is **no baseline** and no active
`ignoreErrors` in the config that runs (`phpstan.neon` — level 8 over `app/`;
the sibling `phpstan.neon.dist` is the template and is *not* the one in use),
and no inline `@var`/casts that exist only to quiet the analyser. There *are*
three `@phpstan-ignore-next-line` suppressions, each with a written reason — in
`app/Enums/WorkStatus.php`, `app/Filament/Resources/Orders/Schemas/OrderItemCustomizationDetails.php`
and `app/Filament/Resources/Products/Schemas/ProductVariationFields.php`. A
new suppression without a reason will not be accepted. The repo is expected to
stay at **0 PHPStan errors**, so a new finding is a change to write, not a
rule to silence.

**`composer.json` is not auto-rewritten by ide-helper — but it used to be.**
`PIANO_IMPLEMENTAZIONE.md` §6 warns that the `barryvdh/laravel-ide-helper`
*Composer plugin* rewrites the `format` script on any Composer command,
including read-only ones. That was true in v2.x; in the installed **v3.7.0**
the package is a plain `library` with no plugin class, it is not in
`allow-plugins`, and `composer show` provably leaves `composer.json`
untouched. So a `composer.json` diff is now always a **real** change someone
made, never plugin noise. Do not commit an unrelated `composer.lock`
regeneration alongside it.

**The branch model is `master` → `production`.** Development happens on
`master`. `production` exists only to trigger `.github/workflows/deploy-production.yml`.
**Releasing = merging `master` into `production`.** The two branches are
expected to be out of sync between releases. `PIANO_IMPLEMENTAZIONE.md` §0
also lists long-lived topic branches (`dark-mode`,
`newwave-products-implementation`, `opencode/*`, `scratch/*`) — **none of them
exist in this clone**; `git branch -a` shows only `master`, `production` and
the remotes. Treat any claim about their content as unverified here.

**`sloppy diff main` will not work here** — the branch is `master`. Use
`vendor/bin/sloppy diff master --fail-on=high`. Note that `CLAUDE.md` still
prescribes `main`, so an agent following it literally will fail; and that
`composer run format` invokes `vendor/bin/sloppy` with **no arguments**, whose
default base is `HEAD` rather than a branch.

---

## 6. Conventions the tooling enforces

Committed rules live in `.ai/rules/`, mapped by `.ai/rules/index.md` by path
glob. Read the matching file before editing a path in scope.

| Scope | Rule |
| --- | --- |
| `app/**` | Use `Carbon\CarbonImmutable` for date handling, types and annotations |
| `app/Models/**` | Use the `#[Fillable([...])]` class attribute, not `protected $fillable` |
| `app/Services/**` | Encapsulate domain workflows, external API calls and sync in dedicated service classes |
| `tests/**` | Pest syntax (`it()`, `test()`, `expect()`); `RefreshDatabase` on Feature tests |
| `resources/views/**` | Livewire Volt single-file components and Flux (`<flux:*>`) components, styled with Tailwind |

Also enforced, from the Laravel Boost and Sloppy rule sets in `CLAUDE.md`:

- Curly braces on every control structure; PHP 8 constructor property
  promotion; explicit return types and parameter type hints everywhere.
- `TitleCase` enum keys. PHPDoc over inline comments; array-shape types in
  PHPDoc.
- Create files with `php artisan make:*` (`--no-interaction`).
- **Never weaken a failing test.** No skipping, no deleting assertions, no
  `assertTrue(true)`. Fix the code, or fix what the test asserts and say why.
  Sloppy reports weakened tests as SL503.
- Before calling a task done: `vendor/bin/pint --dirty --format agent`, then
  `vendor/bin/sloppy diff master --fail-on=high` and fix anything **new** that
  it introduces. Inherited findings are not yours to fix in passing.

---

## 7. Data model

32 migration files. The domain tables (the live schema can differ by
environment — check with `php artisan truss:export --format=llm --compact`):

> **Both local database artefacts are stale.** `database/database.sqlite` has
> 32 tables and is missing `brands`, `collections` and `collection_product`;
> `database/localhost.sql` is older still, with only 28 tables and no
> `brands`, `collections`, `collection_product` or the `campaigns*` tables.
> Do not read either file to decide whether a table exists — read the
> migrations, or run `truss:export` against a live database.

| Domain | Tables |
| --- | --- |
| Accounts | `users`, `addresses` |
| Catalogue | `categories`, `products`, `images`, `media` |
| Variations & stock | `variation_types`, `variation_options`, `product_variation_types`, `product_variation_options`, `product_skus`, `product_sku_options` |
| Pricing | `pricing_tiers`, `category_quantity_discounts`, `shipping_tiers` |
| Promotions | `campaigns`, `campaign_products`, `campaign_product_sku` |
| Collections | `collections`, `collection_product` (composite PK, `sort_order`) |
| Brands | `brands` |
| Orders | `orders`, `order_items`, `transporters` |
| Site content | `portfolio_items`, `newsletter_subscriptions` |
| Framework | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations` |

`orders` carries both paid orders and quotations via `payment_status`.
`order_items.customization_json` preserves the cart configuration;
`design_file_path` plus the `awaiting_file` work state track jobs still
waiting on production files. **A customer-facing design-file upload flow is
not implemented** — customers currently use an external file-transfer service.

---

## 8. Surfaces

| Path | Handler |
| --- | --- |
| `/` | `HomePageController` — hero carousel, featured collections |
| `/catalogo`, `/catalogo/{category:slug}` | `CategoryController` |
| `/catalogo/{category:slug}/{product:slug}` | `ProductController` — detail, configuration, collection index |
| `/collezioni`, `/collezioni/{collection:slug}` | `CollectionController` |
| `/search` | `SearchController` |
| `/outlet` | `OutletProducts` (Livewire) |
| `/portfolio` | `PortfolioController` |
| `/cart` | `CartController` — index |
| `/cart/add` (POST), `/cart/update` (PUT), `/cart/remove` (DELETE), `/cart/remove-multiple` (DELETE), `/cart/clear` (POST) | `CartController` — mutations |
| `/cart/price` | `CartController` — server-side price preview |
| `/checkout` | Livewire component `pages::checkout` |
| `/checkout/session`, `/checkout/quotation` | `CheckoutController` — Stripe session or quotation |
| `/checkout/success`, `/checkout/cancel` | `CheckoutController` |
| `/dashboard/*` | `DashboardController` — auth **+ verified** |
| `/settings` → `/settings/profile`, `/settings/appearance`, `/settings/security` | Fortify, Livewire Volt — in `routes/settings.php`. `auth`; appearance and security also require `verified` |
| `/logout` (POST) | inline closure — auth |
| `/webhooks/stripe` | `WebhookController` — CSRF-exempt |
| `/feed/google-merchant.xml` | `GoogleMerchantFeedController` — gzipped when the crawler accepts it |
| `/sitemap.xml` | `SitemapController` |
| `/up` | health endpoint, registered in `bootstrap/app.php` |
| `/admin/*` | **the Filament panel** (`AdminPanelProvider` sets `->path('admin')`) |
| `POST /admin/products/{product}/toggle-active`, `POST /admin/products/{product}/sync` | `AdminProductController` — plain controllers, **not** Filament, but they share the `/admin` prefix. `auth` + `verified` |
| `/chi-siamo`, `/servizi`, `/contact`, `/privacy`, `/cookie-policy`, `/terms`, `/shipping-returns`, `/palette` | Static Blade views |

Inactive collections 404 for **guests** — the guard is
`abort_unless($collection->is_active || Auth::check(), 404)`, so *any* signed-in
user (not just admins) can view an inactive collection.

### The Google Merchant feed

`GoogleMerchantFeedBuilder` renders it, `MerchantFeedItemWriter` writes it item
by item, `GoogleMerchantFeedCache` caches the rendered result and rebuilds it
in the background when the catalogue changes — the cache is invalidated by
`ProductObserver` and `ProductSkuObserver`, so no cron is needed.

This work existed for a reason: the feed previously issued **20,103 queries**
and blew the 30-second crawler limit with a 500. Eager-loading pricing tiers
and campaigns, reusing the loaded images relation, and memoising the image
list per product brought it to **26 queries**. Those figures are historical
(`PIANO_IMPLEMENTAZIONE.md` §5) — no test pins them — but the property they
describe still holds: keep the query count constant regardless of catalogue
size, and do not add a relation access inside that loop.

---

## 9. Commands

```powershell
php artisan test --compact                    # full suite
vendor/bin/pest --filter=testName             # one test
composer run format                           # ide-helper → rector → pint → sloppy → phpstan
vendor/bin/pint --dirty --format agent        # format only
vendor/bin/sloppy diff master --fail-on=high  # lint the diff you introduced
vendor/bin/phpstan analyse                    # static analysis
php artisan route:list --except-vendor
php artisan truss:export --format=llm --compact   # live schema
npm run build
```

`composer run format` runs `ide-helper:generate`, `ide-helper:meta`,
`ide-helper:models -RW`, Rector, Pint, Sloppy and PHPStan, in that order. The
three `ide-helper:*` commands are required together: `ide-helper:meta` emits
**no** model `@property`/`@method` overrides, so dropping
`ide-helper:models -RW` would silently cost you model autocomplete.

Rector's `withImportNames()` must keep `removeUnusedImports: false`. With it
on, Rector deletes the `use` statements that `ide-helper:models` just wrote
into the model docblocks, and the two tools ping-pong forever.

> **PHP floor is 8.4.1, not 8.4.0.** The Symfony 8.x packages in the lock file
> require `>= 8.4.1`, so a Composer or Artisan command on PHP 8.4.0 fails with
> *"Your Composer dependencies require a PHP version >= 8.4.1"*. Check
> `php -v` before concluding that a command is broken.

---

## 10. Known gaps

The prioritised backlog lives in **`PIANO_IMPLEMENTAZIONE.md` §7**. The
highest-severity correctness items there are worth knowing before you touch
the areas they concern:

- `Order::updateWorkStatusFromItems()` writes a numeric weight where a
  `WorkStatus` value is expected.
- Payment notifications can fire twice (checkout path *and* webhook).
- A pending order can accumulate multiple Stripe Sessions; the webhook does
  not validate the session against the order's stored session.
- Failed NewWave API responses return `null` and can be recorded as `synced`.
- The checkout success page looks up an order by Stripe session ID without
  checking ownership.
- The quotation-review-to-payment handoff is not explicit end to end.
- Customer artwork upload is not implemented.

The dark-theme / UI redesign is described as living on a `dark-mode` branch
that is not merged into `master`. **That branch does not exist in this
clone**, so the redesign cannot be inspected here — treat it as
unavailable, not as absent.

---

## 11. Where the truth lives

| Question | Source of truth |
| --- | --- |
| What is built, what is planned, what is broken | `PIANO_IMPLEMENTAZIONE.md` — current as of 2026-10-22 |
| How to work in this repo (tools, skills, MCP) | `CLAUDE.md` / `AGENTS.md` |
| Path-scoped coding rules | `.ai/rules/*.md`, indexed by `.ai/rules/index.md` |
| Live database structure | `php artisan truss:export --format=llm --compact` |
| Actual routes | `php artisan route:list --except-vendor` |
| Installed package versions | `composer.lock`, `package-lock.json` |
| Configuration | `config/` (read `php artisan config:show <key>`) |

### Staleness found in `PIANO_IMPLEMENTAZIONE.md`

Six items in `PIANO_IMPLEMENTAZIONE.md` were verified as stale against this
clone on 2026-10-22 and have been **corrected in that file** — if you read a
copy predating that date, disregard:

- §0 said `production` was "2 commits behind `master`" and that the collection
  index was "uncommitted … not on either branch". It is now stated as **3
  commits behind** (`production` sits at `32597c6`; `master` has since added
  `886e0f7`, `7f1c251` and `5dc4b09`), and the collection index is recorded
  as **committed on `master`**.
- §0's topic branches (`dark-mode`, `newwave-products-implementation`, …) are
  now recorded as **absent from this clone** — only `master` and `production`
  exist.
- §6's "400 tests" is now **405 tests, 1142 assertions**.
- §6's "the ide-helper Composer plugin rewrites `composer.json`" no longer
  applies at v3.7.0 (see §5) and has been replaced with the correct behaviour
  plus a note on why the `format` pipeline converges.
- §7's "cap the collection index to the first N siblings" is now marked
  **done** (`PRODUCTS_PER_COLLECTION = 8`).
- §2's claim of "no `@phpstan-ignore` comments" now names the three reasoned
  inline suppressions that actually exist, and §3 now flags the stale local
  database artefacts.

The remaining open items in §7 (design-file upload and the rest) are accurate,
as are the correctness bugs listed in §10 above.
