# Pubblicitta24 — Project Status and Implementation Plan

**Repository snapshot:** 2026-10-22
**Status:** Core catalogue, pricing, quotation/order processing, Stripe checkout,
NewWave integration, date-driven offer/outlet campaigns, curated collections
(kits & bundles), a brand catalogue synced from NewWave, a unified product
search, a cached Google Merchant feed, and Filament administration are
implemented. The full test suite (405 tests), PHPStan, Pint, and Sloppy pass at
this snapshot. A configured production deployment workflow exists; this document
does not independently verify the live production environment.

This file describes the implementation found in this repository. It replaces
older plans and counts that no longer match the source. Items under
“Remaining work” are gaps or operational checks, not promises that a feature is
already available.

## 0. Branching model: `master` is the working branch, `production` is for deployment

- **All development happens on `master`.** Features, fixes, and refactors are
  committed and reviewed on `master`; `master` is the source of truth for the
  current state of the application.
- **`production` exists only to trigger deployments.** The workflow
  `.github/workflows/deploy-production.yml` runs on pushes to `production`
  (see section 5). Nothing is developed directly on that branch, and it must not
  be used as a working branch.
- **Releasing means merging `master` into `production`.** The merge is the
  release action: it is what makes a reviewed change deployable. `production`
  therefore lags `master` by design between releases, and the gap is expected
  rather than a sign of lost work.
- At this snapshot `production` is **3 commits behind `master`**: it sits at
  `32597c6` (brand synchronisation, unified search, cached merchant feed),
  while `master` has since added the product-collection index (`886e0f7`), the
  IDE-helper/format-pipeline fix (`7f1c251`), and this agent orientation guide
  (`5dc4b09`). None of the three are deployed yet.
- The only branches in this repository are `master` and `production` (plus their
  remote counterparts). The long-lived topic branches described in earlier
  drafts (`newwave-products-implementation`, `dark-mode`, `opencode/*`,
  `scratch/*`) are **not present** in this clone, so any work recorded against
  them is not reachable from here.

## 1. Product and customer workflows

Pubblicitta24 is a product catalogue and ordering platform for custom apparel,
printing, and related products. It supports both paid orders and requests for
quotation. It is not a quotation-only storefront.

### Catalogue and product configuration

- Browse the home page, category hierarchy, product detail pages, and the
  collections (kits & bundles) index and detail pages.
- Search through a shared search service. The header search box and the
  catalogue page use the same word-based matching over product name, SKU,
  description, category name, and brand name, so both surfaces return the same
  products. The header search box and the catalogue page both go
  through the shared `ProductSearchService`, which uses Laravel Scout
  (`Product::search()`) per word and merges in category and brand name
  matches, so both surfaces share the same Scout-backed matching while
  keeping catalog filters intact.
- Configure variation types and options, including exposed URL selections,
  image-bearing options, and price modifiers.
- Select SKU-backed product variants, availability, and quantities.
- Calculate prices using product pricing tiers, SKU price overrides, category
  quantity discounts, and outlet pricing.
- Campaign dates determine when assigned product offers and outlet SKUs are
  active; campaign scheduling does not mutate product or SKU activation flags.
  The product page shows the active campaigns and their validity window.
- Support apparel/item-based and area-based products, including custom print
  dimensions and sheet/print area calculations.
- Show outlet products and portfolio content.
- Manage product/category images through Spatie Media Library. NewWave products
  can also use remote images and variation-specific product imagery.
- Show detailed product specifications: materials and composition, construction
  features, certifications, and care/washing instructions.

### Collections (kits and bundles)

- A collection is a curated group of products (a "kit" or "bundle") with a
  name, description, banner image, ordering, and active/featured flags.
- Products and collections are many-to-many through `collection_product`, with
  a pivot `sort_order`; a product can belong to several collections.
- `/collezioni` lists the active collections; `/collezioni/{slug}` shows one
  collection with its products, a banner, and a WhatsApp enquiry link.
- Featured collections appear in the home page hero carousel and in a
  dedicated home page section, and the footer links to the index.
- **The product detail page shows an index of the other products of the same
  collection.** When the product belongs to a single collection the index is
  flat ("Prodotti Della Stessa Collezione"); when it belongs to more than one,
  the products are grouped under one heading per collection. The current
  product is excluded, inactive products and inactive collections are filtered
  out, and each group links to its collection page. The data is assembled by
  `App\Services\ProductCollectionIndexService` and rendered by the
  `x-product.collection-index` Blade component, reusing the shared product card
  and grid components. Each group is capped at
  `ProductCollectionIndexService::PRODUCTS_PER_COLLECTION` (8) products; the
  component shows “N di M articoli” and a “Vedi Tutti gli Articoli della
  Collezione” link to the full collection.

### Brands

- Brands are first-class records (`brands`) with a unique name and slug, and a
  product points at one through the nullable `products.brand_id` foreign key.
- The brand name is synchronised from the NewWave API (`productBrand`) and
  resolved to a `Brand` record by `App\Services\BrandResolver`, which
  normalises the incoming name and creates the record when it is new.
- The product page shows the brand name and its logo when one is registered in
  the `logos` media collection; the catalogue sidebar has a brand filter, and
  the brand name participates in the text search and in the Google Merchant
  feed.

### Cart, checkout, and orders

- The cart is session-backed. Customers can add, update, remove, or clear
  configured jobs; multi-SKU quantities and custom dimensions are supported.
- Cart and price-preview endpoints recalculate prices on the server.
- Checkout routes require authentication. Customers can submit a quotation
  request or start a Stripe Checkout Session for payment.
- Quotation requests are stored as `orders` with `payment_status = quotation`;
  there is no separate `quotes` table.
- Stripe webhook processing records payment state. Orders and line items retain
  pricing and customization data for administration.
- Shipping supports delivery and pickup; delivery cost is selected from
  configured shipping tiers. Addresses belong to customer accounts.
- Order work states cover pending, awaiting customer files, processing, ready,
  shipped, and completed. Admins can manage invoices and shipment/tracking
  details.
- Customers with verified accounts can view their dashboard and order history.

### NewWave catalogue synchronization

- NewWave products are represented in the product catalogue and synchronized
  through the GraphQL client, mapper, synchronizer services, and queued jobs.
- Sync state is tracked as pending, syncing, synced, or failed.
- Administrators can trigger product synchronization and choose how deep it
  goes through the `SyncLevel` enum: `Full` (metadata, images, variants,
  prices, availability), `Meta` (name, price, description, technical sheets,
  certifications, washing instructions), or `Availability` (SKU quantities and
  availability only).
- Product detail requests defer an availability refresh for a NewWave product
  when its `updated_at` is at least 12 hours old; a cache lock prevents
  concurrent refreshes for the same product.
- **There is no automatic scheduled catalogue sync in the application
  scheduler.** The current console kernel explicitly leaves scheduled work
  disabled.

## 2. Application architecture

### Runtime and main packages

Versions below reflect the installed project dependencies at this snapshot.

| Area | Package / runtime | Version |
| --- | --- | --- |
| PHP | PHP | 8.4.1+ (8.4 and 8.5 in CI) |
| Backend framework | `laravel/framework` | 13.37.0 |
| Admin panel | `filament/filament` | 5.10.2 |
| Reactive UI | `livewire/livewire` | 4.4.7 |
| Single-file components | `livewire/volt` | 1.11.2 |
| UI components | `livewire/flux` | 2.20.1 |
| CSS/build | Tailwind CSS | 4.x |
| Asset bundler | Vite | 8.x |
| Search (installed, not used by the application) | `laravel/scout` | 11.9.0 |
| Media | `spatie/laravel-medialibrary` | 11.23.10 |
| Payments | `stripe/stripe-php` | 20.3.1 |
| Tests | Pest | 5.3.3 |
| Static analysis | Larastan | 3.10.0 |

The project also uses Fortify for authentication, Laravel Boost for agent and
development tooling, Rector, Pint, Sloppy, Sheath, and the Laravel IDE helper.
Exact dependency constraints are in `composer.json` and `package.json`;
`composer.lock` and `package-lock.json` record the resolved versions.

`laravel/scout` is used for catalogue search: `ProductSearchService` hands each
search word to `Product::search()` (Scout) and intersects the per-word results,
then merges category and brand matches. Catalogue filters stay in SQL.

- Theming is token-based: colors and inversion live in `resources/css/app.css`
  (`--color-*` scales are re-inverted under `.dark`). Use theme-aware utility
  classes such as `bg-gray-50`/`text-gray-950`/`border-gray-200`; never
  hardcode `bg-white`/`bg-black`/`text-white`/`text-black` for surfaces or
  borders, because those do not flip between light and dark.

- `app/Http/Controllers` handles HTTP request/response coordination.
- `app/Http/Requests` contains request validation for cart operations.
- `app/Models` contains Eloquent entities and relationships.
- `app/Concerns` holds the product model traits that keep the entity thin:
  `HasProductCampaigns`, `HasProductExtendedDetails`, `HasProductGallery`,
  `HasProductOutletPricing`, `HasProductPresentation`, `HasProductPricing`,
  and `HasProductVariationDisplay`.
- `app/Services` contains pricing, cart presentation, checkout, media,
  synchronization, notifications, feeds, brand resolution, search, and display
  operations.
- `app/Support` contains value objects shared between services
  (`CheckoutOrderDetails`, `MerchantFeedItem`, `NewWaveProductAttributes`,
  `SlugGenerator`).
- `app/Jobs` contains queued NewWave product and image synchronization jobs.
- `app/Filament/Resources` contains the admin panel resources and schemas.
- `app/Livewire` contains the interactive outlet-products page.
- `resources/views` contains Blade, Livewire, and Volt UI.

The service layer includes focused components such as
`ProductPriceCalculator`, `ProductPricingService`, `QuantityDiscountService`,
`CartManager`, `CartPresentationDataLoader`, `CheckoutOrderService`,
`StripeCheckoutSessionService`, `ProductAvailabilityService`,
`ProductSynchronizer`, `ProductGalleryService`, `ProductSearchService`,
`BrandResolver`, `CampaignProductAssociationService`,
`GoogleMerchantFeedBuilder`, `GoogleMerchantFeedCache`, and
`MerchantFeedItemWriter`.

Single-file components are served through Livewire's component routing
(`Route::livewire(...)`) rather than the previous `Volt::route(...)` helper
after the Volt/Livewire 4 upgrade.

### Conventions enforced by the static analysis

- **Do not narrow the parameter type of an eager-load constraint closure.**
  Larastan types the closure passed to `with()` as
  `Closure(Relation): mixed`, so a closure declared as
  `fn (BelongsToMany $query) => ...` is a type error even when the relation
  really is a `BelongsToMany` and the code works at runtime. Use the untyped
  `fn ($query) => ...` form that the rest of the codebase uses: it satisfies
  the analyser and keeps working for any relation type. The collection index
  service (`App\Services\ProductCollectionIndexService`) originally used the
  narrower type and had to be changed for this reason.
- **Fix a PHPStan finding at its cause, never with a suppression.** There is
  no PHPStan baseline and no active `ignoreErrors` entry in `phpstan.neon`.
  The only suppressions are three inline `@phpstan-ignore-next-line`
  comments, each carrying the reason it exists (a false-positive match
  expression in `app/Enums/WorkStatus.php`, a Filament scaffold constant in
  `OrderItemCustomizationDetails.php`, and a wrong vendor stub return type in
  `ProductVariationFields.php`). A new suppression must do the same: state
  why, and be reviewable. The repository must stay at 0 PHPStan errors, so a
  new finding is a change to write, not a rule to silence.

## 3. Data model

The repository contains 32 migration files. The application schema includes
the following domain tables (the live schema can differ by environment):

| Domain | Tables | Purpose |
| --- | --- | --- |
| Accounts | `users`, `addresses` | Fortify accounts, user role/status and customer shipping/billing details |
| Catalogue | `categories`, `products`, `images`, `media` | Product/category hierarchy, legacy/remote images, and Media Library assets |
| Variations and stock | `variation_types`, `variation_options`, `product_variation_types`, `product_variation_options`, `product_skus`, `product_sku_options` | Product options, modifier configuration, SKU availability and outlet overrides |
| Pricing | `pricing_tiers`, `category_quantity_discounts`, `shipping_tiers` | Product/SKU quantity pricing, category discounts, and delivery rates |
| Promotions | `campaigns`, `campaign_products`, `campaign_product_sku` | Scheduled campaigns and their exclusive product‑offer and outlet‑SKU assignments |
| Collections | `collections`, `collection_product` | Curated kits/bundles, their product membership and the pivot ordering |
| Brands | `brands` | Brand catalogue, referenced by `products.brand_id` |
| Orders | `orders`, `order_items`, `transporters` | Paid orders and quotations, customizations, fulfillment state, invoices and tracking |
| Site content | `portfolio_items`, `newsletter_subscriptions` | Portfolio and newsletter subscribers |
| Laravel infrastructure | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations` | Framework cache, queue, session, auth, and migration state |

`orders` stores both checkout orders and quotation requests using
`payment_status`. `order_items.customization_json` preserves the cart
configuration; `design_file_path` and the `awaiting_file` work state support
tracking jobs that still need production files. A complete customer‑facing
design‑file upload workflow was not confirmed in the routes inspected.

Campaigns are active when the current time falls between `starts_at` and
`ends_at`; both bounds are nullable. Products with an `offer_price` and outlet
SKUs must be assigned to a campaign before their campaign pricing or outlet
visibility is active. An assigned item remains associated after the campaign
ends until an administrator removes it. `CampaignProductAssociationService`
enforces that a product offer and an outlet SKU belong to a single campaign at
a time, replacing the previous association transactionally.

`collection_product` uses a composite primary key (`collection_id`,
`product_id`) with cascading deletes and a `sort_order` used for the ordering
of the products inside a collection.

The checked‑in `.env.example` defaults to SQLite and a synchronous queue. Those
are development defaults, not a statement about the production database or
queue configuration.

**The local database artefacts are stale and must not be used as the schema.**
`database/database.sqlite` (32 tables) predates the `brands`, `collections`
and `collection_product` tables, and `database/localhost.sql` (28 tables)
predates those and every `campaigns*` table. Read the migrations, or export
the live structure with `php artisan truss:export --format=llm --compact`,
before reasoning about the schema.

## 4. Current routes and surfaces

The web route list contains 143 registered routes, of which 79 are application
routes (the remainder belong to vendor packages such as Fortify authentication
and the Livewire/Volt internals). The principal application routes are:

| Path | Handler | Purpose / access |
| --- | --- | --- |
| `/` | `HomePageController` | Home page, hero carousel and featured collections |
| `/catalogo` | `CategoryController` | Catalogue root |
| `/catalogo/{category:slug}` | `CategoryController` | Category and subcategory page |
| `/catalogo/{category:slug}/{product:slug}` | `ProductController` | Product detail, configuration, and the collection index |
| `/collezioni` | `CollectionController` | Collections (kits & bundles) index |
| `/collezioni/{collection:slug}` | `CollectionController` | Collection detail and its products |
| `/search` | `SearchController` | Catalogue search |
| `/outlet` | `OutletProducts` (Livewire) | Outlet listing |
| `/portfolio` | `PortfolioController` | Portfolio index |
| `/portfolio/{portfolioItem}` | `PortfolioController` | Portfolio detail || `/cart`, `/cart/*` | `CartController` | Cart, mutations, and `/cart/price` preview |
| `/checkout` | Livewire component (`pages::checkout`) | Authenticated checkout |
| `/checkout/session` | `CheckoutController` | Authenticated Stripe checkout or quotation creation |
| `/checkout/quotation` | `CheckoutController` | Authenticated direct quotation request |
| `/checkout/success`, `/checkout/cancel` | `CheckoutController` | Authenticated checkout return pages |
| `/dashboard/*` | `DashboardController` | Verified account order/address views |
| `/webhooks/stripe` | `WebhookController` | Stripe webhook receiver |
| `/feed/google-merchant.xml` | `GoogleMerchantFeedController` | Google Merchant product feed |
| `/sitemap.xml` | `SitemapController` | XML sitemap |
| `/admin/*` | Filament | Brand, campaign, product, category, variation, SKU/outlet, order, user, shipping, transporter, portfolio, collection, and newsletter administration |
| `/chi-siamo`, `/servizi`, `/contact` | Static Blade views | Informational pages |
| `/privacy`, `/cookie-policy`, `/terms`, `/shipping-returns` | Static Blade views | Legal and policy pages |

Checkout requires authentication but is not grouped under the `verified`
middleware in `routes/web.php`. Dashboard routes require both authentication
and email verification. Inactive collections return 404 to guests.

## 5. Operations, SEO, and integrations

### Payments and external integrations

- Stripe Checkout Sessions are created through a dedicated service; webhook
  handling is routed separately from browser checkout.
- NewWave GraphQL endpoint, token, and TLS verification are environment
  configured.
- Google Merchant feed, Google Customer Reviews badge/checkout opt‑in, and
  Google Analytics consent updates are present in the application.
- SMTP, Stripe, NewWave, and other environment‑dependent integrations require
  valid deployment secrets and external service configuration.

### Google Merchant feed

- The feed is rendered by `GoogleMerchantFeedBuilder` and written item by item
  by `MerchantFeedItemWriter`; the feed now includes brand and extended product
  details (materials, washing instructions).
- The rendered feed is cached by `GoogleMerchantFeedCache` and rebuilt in the
  background whenever the catalogue changes, so no cron job is required. The
  endpoint serves the feed gzipped.
- Performance was the reason for this work: the feed previously issued 20,103
  queries and exceeded the 30 second crawler limit, returning a 500. Eager
  loading the pricing tiers and campaigns, reusing the loaded images relation,
  and memoising the image list per product brought it to 26 queries.

### SEO and content

- The shared layout emits page metadata, canonical URLs, Open Graph/Twitter
  metadata, and LocalBusiness structured data.
- Sitemap and Google Merchant XML feed endpoints are implemented. The sitemap
  namespace was corrected to satisfy Google Search Console requirements.
- The site has local‑business metadata for Fiuggi/Ciociaria, but the
  city/service‑specific landing routes described in older drafts are not
  registered. Do not treat those proposed URLs as implemented pages.
- A cookie banner stores essential/all consent and updates Google consent
  state. Policy pages describe the intended consent behavior.

### Production deployment

`.github/workflows/deploy-production.yml` deploys on pushes to `production`.
It installs production Composer dependencies, runs `npm ci` and the Vite build,
then transfers and extracts an archive over SSH using GitHub secrets. Because
`production` is deployment‑only, a release is a merge of `master` into
`production` (see section 0). Successful workflow execution, server‑side
prerequisites, queue workers, cron setup, backups, and live service
configuration must be checked in the deployment environment; they cannot be
inferred from the workflow file alone.

## 6. Quality and verification

- Tests use Pest and are organized under `tests/Architecture`, `tests/Feature`,
  and `tests/Unit`.
- At this snapshot, the repository contains 79 files named `*Test.php`.
- The full suite passes: **405 tests, 1142 assertions**. Two warnings are
  reported and are not failures.
- PHPStan/Larastan reports **0 errors** (it was 17 before the recent
  refactoring of the feed writer and checkout service).
- Sloppy reports no new findings at high severity or above on the current
  diff, with a score of 100/100. The `format` Composer script runs the IDE
  helper generators (`ide-helper:generate`, `ide-helper:meta`,
  `ide-helper:models -RW`), Rector, Pint, Sloppy, and PHPStan/Larastan.
- **The IDE helper is a plain Composer library, not a plugin, and it does not
  touch `composer.json`.** At `barryvdh/laravel-ide-helper` v3.7.0 the package
  declares no Composer plugin class and is not listed in `config.allow-plugins`,
  so no Composer command — including read-only ones such as `composer show` —
  can rewrite the file. The three `ide-helper:*` commands in `scripts.format`
  were added by hand and are stable. **Any `composer.json` diff is therefore a
  real change**: review it, and never commit an unrelated `composer.lock`
  regeneration alongside it.
- **The `format` pipeline converges; it is not a loop.** The oscillation
  between `ide-helper:models -RW` and Rector came from Rector's
  `withImportNames()` default, which enables `removeUnusedImports` and deleted
  the `use` statements the IDE helper had just written into the model
  docblocks. `rector.php` now passes `removeUnusedImports: false`
  explicitly, and the two tools agree: the model files are byte-identical
  after repeated runs. `ide-helper:models -RW` is the command that keeps the
  `@property`/`@method` overrides on the models current;
  `ide-helper:generate` and `ide-helper:meta` do **not** produce them, so
  running only those leaves the model docblocks stale.
- GitHub Actions has separate test and lint workflows. The test workflow runs
  against PHP 8.4 and 8.5, installs Node 22 dependencies, builds assets, and runs
  Pest. The production workflow builds with Node 24 and PHP 8.5.
- The project‑local `npm` package was removed; it was the source of the npm
  audit findings. The normal system npm CLI remains in use. The dependency
  audit was reported clean after removal.

Useful commands:

```powershell
php artisan test --compact
composer run format
npm audit
npm run build
php artisan route:list --except-vendor
php artisan truss:export --format=llm --compact
```

## 7. Remaining work and operational checks: TO DO

### Priority correctness, payment, and fulfillment work

1. **Fix order work‑status aggregation.** `Order::updateWorkStatusFromItems()`
   currently writes a numeric weight as the order status, while status labels
   and workflow code expect a `WorkStatus` value. Persist the actual enum
   value selected by the aggregation rule and add regression coverage for
   item‑status changes, deletions, and mixed item states.
2. **Send payment notifications once.** The current payment completion path and
   Stripe webhook both invoke payment notifications. Keep a single notification
   owner and test that a successful webhook sends one customer confirmation
   and one notification per admin, including when Stripe redelivers the event.
3. **Make Stripe payment‑session handling safe for repeat attempts.** A
   customer's pending order can receive multiple Checkout Sessions, and the
   webhook currently processes order metadata without checking the session
   against the order's stored active session. Reuse or expire prior sessions,
   validate the session/order association, and define how late or duplicate
   successful charges are reconciled. Test repeated checkout attempts,
   mismatched/late sessions, idempotent inventory changes, and the chosen
   duplicate‑charge handling.
4. **Do not mark failed NewWave requests as synced.** API failures currently
   return `null`, the synchronizer returns without an error, and the job can
   then record a successful sync. Distinguish successful data retrieval from
   failed or malformed responses so the job records an honest terminal state
   and useful diagnostics. Test success, API failure, malformed data,
   exceptions, and a missing product.
5. **Scope checkout success lookups to the signed‑in customer.** The success
   page looks up an order by Stripe session ID without checking its owner.
   Scope access to the authenticated user (or use an appropriately signed,
   short‑lived completion mechanism) and test that one customer cannot retrieve
   another customer's order or expose their personal data through the page.
6. **Complete the quotation‑review‑to‑payment journey.** Customers can submit a
   quotation, and admins can edit order‑item prices, but make the full handoff
   explicit: review customer notes/configuration, revise line prices and totals,
   notify the customer of the final offer, and let them accept/pay that
   finalized amount. Preserve an auditable distinction between an unreviewed
   request and an offer ready for payment; ensure an old Stripe session cannot
   charge a superseded total. Test acceptance/payment and rejection, revision,
   and stale‑session paths.
7. **Finish customer design‑file delivery.** Add the branded upload flow to
   product customization and carry its state through the cart, quotation or
   checkout, and order item so customers can attach artwork before payment or
   quote review rather than switching to an external file‑transfer service.
   Use private temporary storage, explicit allowed file types and size limits,
   content validation, safe generated names, authorization, cleanup of abandoned
   uploads, and reliable association to the correct order item/SKU. Define
   whether files are processed asynchronously and how upload/processing errors
   are shown. Retain a documented manual fallback until the full flow is live.

### Search visibility and business growth

1. **Build a quality‑controlled local landing‑page framework.** If the service
   area and local offering are confirmed, implement data‑driven, allowlisted
   pages such as `/stampa-personalizzata/{provincia}/{comune}` for priority
   locations on the Roma‑Napoli axis. Use one maintainable template with genuinely
   useful, location‑specific copy, accurate titles, headings, metadata, image
   alt text, canonical URLs, internal links, and sitemap entries. Verify any
   claims about rapid delivery, in‑person consultation, or file checks before
   publishing them. Avoid thin or duplicate pages; test valid locations,
   unknown slugs, metadata, and sitemap inclusion.
2. **Fill the collections with real, sector‑specific kits.** The collections
   infrastructure (model, pivot, public index and detail pages, Filament
   resource, home page placement, and the product‑page index) is implemented,
   but the catalogue content is still to be authored. Create curated verticals
   such as HoReCa and construction/workwear using suitable existing products
   (e.g. BASIC POLO or MIAMI HOODY), with factual material and use‑case
   benefits rather than generic duplicated copy. Add relevant metadata,
   canonical/internal links and sitemap entries, and test product membership and
   invalid sector slugs.
3. ~~**Decide the role of the collection index on the product page.**~~ **Done.**
   The index is capped: `ProductCollectionIndexService::PRODUCTS_PER_COLLECTION`
   is `8`, and the product page shows “N di M articoli” with a
   “Vedi Tutti gli Articoli della Collezione” link to the full collection.
4. **Validate B2B volume pricing and quotation presentation.** The application
   already has pricing tiers and category quantity discounts. Confirm these
   cover approved bulk‑price rules; if not, implement missing rules and a clear
   tier/discount matrix without bypassing server‑side price calculation.
   Test displayed and charged totals at tier boundaries and across quote
   revisions.
5. **Request authentic local customer reviews.** Establish a process to invite
   customers to leave honest reviews on the business's Google profile without
   incentives, gating, or pressure to leave positive feedback. Treat the
   proposed request for 5–10 reviews as a planning target, not a guaranteed SEO
   outcome.

### Test coverage backlog

1. **Checkout and payment‑session failures:** cover successful pickup checkout
   without a shipping address and payment for the customer's own pending order.
   Reject invalid or missing fields, foreign or already‑processed orders, and
   Stripe session‑creation failures without creating unauthorized orders or
   losing the cart. Foreign‑address rejection is already covered.
2. **Stripe webhook handling:** cover malformed payloads, invalid signatures,
   irrelevant event types, and events with missing or unknown order metadata.
   On a valid event, assert one payment transition, one inventory decrement,
   and exactly‑once notifications despite event redelivery. Missing signature
   and successful repeated‑delivery responses are already covered.
3. **NewWave job lifecycle:** assert successful jobs record `synced`, 100%
   progress, and a sync timestamp. Failures and malformed responses must not
   appear successful; thrown failures must record `failed`, and missing
   products must be safely skipped. The `SyncLevel` picker is covered; the
   level‑specific effects on a product are not.
4. **Cart mutation boundaries:** add partial bulk‑removal coverage proving
   unselected items remain. Invalid or unknown item keys, invalid quantities,
   and malformed bulk‑removal input must not change or remove unrelated items.
   Existing tests cover successful single‑item updates/removals.
5. **Cart pricing contract:** assert exact unit and total prices, quantity, and
   discount status for representative tiered, area‑based, and selected‑option
   cases. Cover invalid option/configuration combinations. Existing tests
   check the basic JSON shape and malformed field validation.
6. **Admin action authorization:** admin success and non‑admin denial for one
   action are covered. Add guest checks and non‑admin denial for both product
   toggle and sync actions, plus unknown product identifiers.
7. **Order ownership and payment lifecycle:** test cross‑customer checkout
   success lookup denial; pending‑order reuse; duplicate Stripe session attempts;
   stale/mismatched webhook sessions; item‑to‑order status aggregation; payment
   notification counts; and quote revision, customer acceptance, and payment
   of the final offer.
8. **Artwork upload lifecycle:** test accepted file types and size limits,
   rejected/invalid uploads, private storage and authorization, abandoned
   temporary‑file cleanup, and correct association to the intended order item
   after both payment and quotation submission.
9. **Landing‑page behavior:** test allowed/unknown locality and sector slugs,
   product filtering, metadata/canonical URLs, and sitemap entries; confirm
   page copy only makes verified service and material claims.
10. **Brand and search regressions:** cover brand resolution (normalisation,
    reuse of an existing record, slug uniqueness) and the shared search
    semantics for multi‑word terms, brand names, and category names, so the
    header and the catalogue cannot drift apart again.

### Operations, privacy, and maintenance

1. **Production release safeguards:** add a required CI gate for the
   `production` branch and pull requests targeting it. Define a safe release
   sequence for dependencies, database migrations, health checks, and rollback
   or recovery; avoid an unverified in‑place deploy being treated as a
   successful release. Test the deployment procedure against a staging
   environment before relying on it. The merge from `master` to `production` is
   the release trigger, so the gate belongs on that path.
2. **Account deletion and data lifecycle:** define and implement an authorized
   user deletion/anonymization process that respects order‑retention and
   accounting requirements. A dedicated anonymization service was not found.
3. **Scheduled synchronization:** decide whether full NewWave catalog sync
   needs scheduled execution. It is not currently scheduled; product detail
   pages only defer availability refreshes for stale products.
4. **Production operations:** document and verify the production database,
   queue worker, scheduler (if added), storage/media disks, mail, Stripe webhook
   registration, monitoring, backups, and restore procedure. Confirm that the
   deployment process runs migrations deliberately and that recovery is tested.
5. **Performance baselines:** add workload‑based query‑count or load benchmarks
   if operational traffic warrants them. The Google Merchant feed work shows
   the value: a feed that took 20,000+ queries was invisible to crawlers. Do
   not treat the absence of benchmark suites as a failure of ordinary
   functional test coverage.

### UI/UX redesign

This work exists on the `dark-mode` branch and has **not** been merged into
`master`.

1. **Dark theme implementation:** Apply a deep charcoal/black base theme with
   crimson accent colors for primary actions and highlights across the site.
   Use Tailwind's dark‑mode utilities and ensure sufficient contrast for
   accessibility.
2. **Full‑page hero component:** Replace the standard top banner with an
   immersive hero displaying a high‑quality lifestyle mockup (e.g., a moody
   hoodie or neon sign) against a dark background. Include a large headline:
   **“La Tua Immagine. Stampata Senza Stress.”**
   Add two central CTA buttons:
   - **“Ho il file pronto”** – directs to the upload‑and‑print flow.
   - **“Ho bisogno di grafica”** – directs to the design‑assist flow.
3. **Product listing redesign:**
   - Use dark‑grey cards that blend into the black background.
   - Add interactive hover effects (subtle glow or image swap to lifestyle view).
   - Remove visible grid lines; rely on whitespace for separation.
4. **Process timeline:** Transform the textual “Come funziona” four‑step
   section into a horizontal timeline component with icons. Highlight step 3
   ("Controllo Gratuito") in crimson to emphasize the trust‑builder.
5. **Component updates:** Update the related Blade/Livewire/Volt components and
   Tailwind configuration accordingly. Ensure the new UI is fully responsive
   and meets WCAG contrast and focus‑state requirements.
6. **Testing:** Add visual regression tests for the new hero, CTA buttons,
   product cards, and timeline components to guard against unintended UI
   regressions.

These visual enhancements do not affect core business logic but require
coordination with the design team and updates to the front‑end asset pipeline
(Vite build). The dark theme should be togglable for users who prefer light mode.

## 8. Snapshot caveats

- Counts and package versions above are repository/runtime observations for the
  snapshot date and will change as the project evolves.
- The `master`/`production` split described in section 0 is the release model
  in use at this snapshot; the two branches are not in sync, and the
  differences between them are expected rather than accidental.
- A green local test or formatting run does not by itself prove production
  readiness, deployment success, payment‑provider configuration, legal
  compliance, or recovery capability.
- Update this document when routes, schema, deployment workflows, or major
  customer workflows change. Prefer code, migrations, configuration, and
  current test results over older prose when they disagree.
