# Pubblicitta24 — Project Status and Implementation Plan

**Repository snapshot:** 2026-10-04
**Status:** Core catalogue, pricing, quotation/order processing, Stripe checkout,
NewWave integration, date-driven offer/outlet campaigns, and Filament
administration are implemented. Targeted campaign and pricing regression tests
passed for this snapshot. A configured production deployment workflow exists;
this document does not independently verify the live production environment.

This file describes the implementation found in this repository. It replaces
older plans and counts that no longer match the source. Items under
“Remaining work” are gaps or operational checks, not promises that a feature is
already available.

## 1. Product and customer workflows

Pubblicitta24 is a product catalogue and ordering platform for custom apparel,
printing, and related products. It supports both paid orders and requests for
quotation. It is not a quotation-only storefront.

### Catalogue and product configuration

- Browse the home page, category hierarchy, and product detail pages.
- Search through Laravel Scout.
- Configure variation types and options, including exposed URL selections,
image-bearing options, and price modifiers.
- Select SKU-backed product variants, availability, and quantities.
- Calculate prices using product pricing tiers, SKU price overrides, category
  quantity discounts, and outlet pricing.
- Campaign dates determine when assigned product offers and outlet SKUs are
active; campaign scheduling does not mutate product or SKU activation flags.
- Support apparel/item-based and area-based products, including custom print
  dimensions and sheet/print area calculations.
- Show outlet products and portfolio content.
- Manage product/category images through Spatie Media Library. NewWave products
  can also use remote images and variation-specific product imagery.

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
- Administrators can trigger product synchronization.
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
| PHP | PHP | 8.5 |
| Backend framework | `laravel/framework` | 13.34.0 |
| Admin panel | `filament/filament` | 5.9.0 |
| Reactive UI | `livewire/livewire` | 4.4.7 |
| Single-file components | `livewire/volt` | 1.11.2 |
| UI components | `livewire/flux` | 2.20.1 |
| CSS/build | Tailwind CSS | 4.x |
| Asset bundler | Vite | 8.x |
| Search | `laravel/scout` | 11.8.0 |
| Media | `spatie/laravel-medialibrary` | 11.23.8 |
| Payments | `stripe/stripe-php` | 20.3.1 |
| Tests | Pest | 5.3.0 |
| Static analysis | Larastan | 3.10.0 |

The project also uses Fortify for authentication, Laravel Boost for agent and
development tooling, Rector, Pint, Sloppy, and Sheath. Exact dependency
constraints are in `composer.json` and `package.json`; `composer.lock` and
`package-lock.json` record the resolved versions.

### Main code boundaries

- `app/Http/Controllers` handles HTTP request/response coordination.
- `app/Http/Requests` contains request validation for cart operations.
- `app/Models` contains Eloquent entities and relationships.
- `app/Services` contains pricing, cart presentation, checkout, media,
  synchronization, notifications, feeds, and display operations.
- `app/Jobs` contains queued NewWave product and image synchronization jobs.
- `app/Filament/Resources` contains the admin panel resources and schemas.
- `app/Livewire` contains the interactive outlet-products page.
- `resources/views` contains Blade, Livewire, and Volt UI.

The service layer includes focused components such as
`ProductPriceCalculator`, `ProductPricingService`, `QuantityDiscountService`,
`CartManager`, `CartPresentationDataLoader`, `CheckoutOrderService`,
`StripeCheckoutSessionService`, `ProductAvailabilityService`,
`ProductSynchronizer`, `ProductGalleryService`, and
`GoogleMerchantFeedBuilder`.

## 3. Data model

The repository contains 29 migration files. The application schema includes
the following domain tables (the live schema can differ by environment):

| Domain | Tables | Purpose |
| --- | --- | --- |
| Accounts | `users`, `addresses` | Fortify accounts, user role/status and customer shipping/billing details |
| Catalogue | `categories`, `products`, `images`, `media` | Product/category hierarchy, legacy/remote images, and Media Library assets |
| Variations and stock | `variation_types`, `variation_options`, `product_variation_types`, `product_variation_options`, `product_skus`, `product_sku_options` | Product options, modifier configuration, SKU availability and outlet overrides |
| Pricing | `pricing_tiers`, `category_quantity_discounts`, `shipping_tiers` | Product/SKU quantity pricing, category discounts, and delivery rates |
| Promotions | `campaigns`, `campaign_products`, `campaign_product_sku` | Scheduled campaigns and their exclusive product‑offer and outlet‑SKU assignments |
| Orders | `orders`, `order_items`, `transporters` | Paid orders and quotations, customizations, fulfillment state, invoices and tracking |
| Site content | `portfolio_items`, `newsletter_subscriptions` | Portfolio and newsletter subscribers |
| Laravel infrastructure | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations` | Framework cache, queue, session, auth, and migration state |

`orders` stores both checkout orders and quotation requests using
`payment_status`. `order_items.customization_json` preserves the cart
configuration; `design_file_path` and the `awaiting_file` work state support
tracking jobs that still need production files. A complete customer‑facing
design‑file upload workflow was not confirmed in the routes inspected.

Campaigns are active when the current time falls between `starts_at` and
`ends_at`. Products with an `offer_price` and outlet SKUs must be assigned to a
campaign before their campaign pricing or outlet visibility is active. An
assigned item remains associated after the campaign ends until an administrator
removes it.

The checked‑in `.env.example` defaults to SQLite and a synchronous queue. Those
are development defaults, not a statement about the production database or
queue configuration.

## 4. Current routes and surfaces

The web route list contains 68 registered routes, including vendor/admin and
authentication routes. The principal application routes are:

| Path | Handler | Purpose / access |
| --- | --- | --- |
| `/` | `HomePageController` | Home page |
| `/catalogo` | `CategoryController` | Catalogue root |
| `/catalogo/{category:slug}` | `CategoryController` | Category and subcategory page |
| `/catalogo/{category:slug}/{product:slug}` | `ProductController` | Product detail and configuration |
| `/search` | `SearchController` | Catalogue search |
| `/outlet` | `OutletProducts` (Livewire) | Outlet listing |
| `/portfolio` | `PortfolioController` | Portfolio |
| `/cart`, `/cart/*` | `CartController` | Cart, mutations, and `/cart/price` preview |
| `/checkout` | Volt component | Authenticated checkout |
| `/checkout/session` | `CheckoutController` | Authenticated Stripe checkout or quotation creation |
| `/checkout/quotation` | `CheckoutController` | Authenticated direct quotation request |
| `/checkout/success`, `/checkout/cancel` | `CheckoutController` | Authenticated checkout return pages |
| `/dashboard/*` | `DashboardController` | Verified account order/address views |
| `/webhooks/stripe` | `WebhookController` | Stripe webhook receiver |
| `/feed/google-merchant.xml` | `GoogleMerchantFeedController` | Google Merchant product feed |
| `/sitemap.xml` | `SitemapController` | XML sitemap |
| `/admin/*` | Filament | Campaign, product, category, variation, SKU/outlet, order, user, shipping, transporter, portfolio, and newsletter administration |
| `/chi-siamo`, `/servizi`, `/contact` | Static Blade views | Informational pages |
| `/privacy`, `/cookie-policy`, `/terms`, `/shipping-returns` | Static Blade views | Legal and policy pages |

Checkout requires authentication but is not grouped under the `verified`
middleware in `routes/web.php`. Dashboard routes require both authentication
and email verification.

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

### SEO and content

- The shared layout emits page metadata, canonical URLs, Open Graph/Twitter
metadata, and LocalBusiness structured data.
- Sitemap and Google Merchant XML feed endpoints are implemented.
- The site has local‑business metadata for Fiuggi/Ciociaria, but the
city/service‑specific landing routes described in older drafts are not
registered. Do not treat those proposed URLs as implemented pages.
- A cookie banner stores essential/all consent and updates Google consent
state. Policy pages describe the intended consent behavior.

### Production deployment

`.github/workflows/deploy-production.yml` deploys on pushes to `production`.
It installs production Composer dependencies, runs `npm ci` and the Vite build,
then transfers and extracts an archive over SSH using GitHub secrets. Successful
workflow execution, server‑side prerequisites, queue workers, cron setup,
backups, and live service configuration must be checked in the deployment
environment; they cannot be inferred from the workflow file alone.

## 6. Quality and verification

- Tests use Pest and are organized under `tests/Architecture`, `tests/Feature`,
and `tests/Unit`.
- At this snapshot, the repository contains 69 files named `*Test.php`.
- The project reports passing tests and a clean `composer run format` at the
snapshot date. The `format` Composer script runs Rector, Pint, Sloppy, and
PHPStan/Larastan.
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
2. **Develop industry‑specific collections and landing pages.** If supported
   by the catalogue and production capabilities, create curated verticals such
   as HoReCa and construction/workwear, using suitable existing products (e.g.
   BASIC POLO or MIAMI HOODY). Explain factual material and use‑case benefits
   rather than generic duplicated copy. Add relevant metadata, canonical/internal
   links and sitemap entries, and test product membership and invalid sector
   slugs.
3. **Validate B2B volume pricing and quotation presentation.** The application
   already has pricing tiers and category quantity discounts. Confirm these
   cover approved bulk‑price rules; if not, implement missing rules and a clear
   tier/discount matrix without bypassing server‑side price calculation.
   Test displayed and charged totals at tier boundaries and across quote
   revisions.
4. **Request authentic local customer reviews.** Establish a process to invite
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
   products must be safely skipped.
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

### Operations, privacy, and maintenance

1. **Production release safeguards:** add a required CI gate for the
   `production` branch and pull requests targeting it. Define a safe release
   sequence for dependencies, database migrations, health checks, and rollback
   or recovery; avoid an unverified in‑place deploy being treated as a
   successful release. Test the deployment procedure against a staging
   environment before relying on it.
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
   if operational traffic warrants them. Do not treat the absence of benchmark
   suites as a failure of ordinary functional test coverage.

### UI/UX redesign

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
   section into a horizontal timeline component with icons. Highlight step 3
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
- A green local test or formatting run does not by itself prove production
readiness, deployment success, payment‑provider configuration, legal
compliance, or recovery capability.
- Update this document when routes, schema, deployment workflows, or major
customer workflows change. Prefer code, migrations, configuration, and
current test results over older prose when they disagree.
