# Pubblicitta24 — Status and implementation plan

**Reviewed:** 2026-11-04
**Purpose:** Separate implemented behavior from the work that still needs a
decision or implementation. This is a working backlog, not a release
readiness statement. Confirm an item against current code and tests before
starting; implementation, migrations, configuration and test results are the
source of truth.

For domain rules and code locations, see [ARCHITECTURE.md](./ARCHITECTURE.md).
For agent workflow and validation commands, see [AGENTS.md](./AGENTS.md).

## 1. Current product baseline

The repository includes the following main capabilities:

- Product catalogue, categories, configurable variations and SKU availability.
- Tiered, category-discount, campaign, SKU and area-based pricing.
- Product and outlet campaigns, curated collections and brands.
- NewWave product synchronization and product search.
- Session-backed cart, authenticated Stripe checkout, quotation requests and
  order administration.
- Filament administration, product media, portfolio content and a cached
  Google Merchant feed.

The product detail collection index is already implemented and capped at eight
products per collection. Do not re-add it as backlog work. The old note that
the `dark-mode` branch was absent or its work unmerged is also stale: its tip
is an ancestor of `master`. Review the current theme and UI in source before
proposing redesign work.

The last recorded full-suite result is **406 tests and 1,144 assertions on
2026-10-22**. Dependencies were updated after that snapshot, and this
documentation refresh did not rerun the suite; treat that number as historical,
not as a current green build.

## 2. Release model

- Develop and review changes on `master`.
- Develop and review changes on `master`; publish the approved release by
  merging or fast-forwarding `master` into `production`.
- Pushing to `production` publishes source but does **not** deploy the
  application. Deployment is manually initiated on the production server by
  running `./deploy.sh` (which defaults to pulling `origin/production`).
- Do not run `deploy.sh` from a development workstation as part of routine
  validation. Confirm the deployment result and production health on the
  server.
- Branches and remotes can change. Inspect the current Git graph before
  describing what is deployed or how far branches differ; do not preserve
  fixed ahead/behind counts in this document.
- A successful deployment command does not by itself prove production health,
  queue operation, backups or recovery capability. Verify these in the
  deployment environment.

## 3. Priority 0 — correctness, payment and fulfilment

These are high-risk gaps observed in the current source on the review date.
Recheck existing tests and any intervening changes before implementation.

1. **Persist a valid order work status.** `Order::updateWorkStatusFromItems()`
   selects the lowest item-status weight and currently writes that numeric
   weight as the order status, rather than a `WorkStatus` value. Store the
   selected enum value and cover mixed item states, item updates/deletions and
   the empty-order case.
2. **Make Stripe completion single-owner and session-bound.**
   `Order::completePayment()` sends payment notifications, and
   `WebhookController` also sends them after calling it. Choose one notification
   owner. Bind a successful webhook to the order's active Stripe session;
   decide how repeated checkout attempts and late or duplicate successful
   charges are reconciled. Preserve idempotent payment and inventory behavior.
   Test webhook redelivery, mismatched/stale sessions, duplicate attempts and
   exactly-once notifications.
3. **Authorize checkout success lookups.** `CheckoutController::success()`
   looks up orders by Stripe session ID without scoping the result to the
   signed-in customer. Enforce ownership (or use an appropriately signed,
   short-lived completion mechanism) and test that another customer cannot
   view the order or its personal data.
4. **Report NewWave sync failures honestly.** `NwgApiClient` logs API and
   transport failures and returns `null`; `ProductSynchronizer` can return
   normally when data is absent, after which `SyncNewWaveProductJob` marks the
   product synced. Distinguish valid data from failure so the job records an
   accurate failed state and useful diagnostics. Cover unsuccessful responses,
   malformed/missing data, exceptions, success and a missing product.
5. **Complete quotation review and payment.** Make the transition from an
   unreviewed quotation to an admin-approved offer explicit: revise line prices
   and totals, notify the customer, then allow acceptance/payment of that final
   amount. Keep revisions auditable and invalidate sessions for superseded
   totals. Test revision, acceptance, rejection and stale-session behavior.
6. **Implement customer artwork upload.** Add a customer-facing upload flow
   and carry its state through product configuration, cart, quotation/checkout
   and the correct order item. Define private temporary storage, file type and
   size validation, authorization, safe names, abandoned-file cleanup and
   processing/error UX. Keep a manual file-transfer fallback until the flow is
   live.

## 4. Priority 1 — catalogue quality and growth

These items need business/content confirmation as well as implementation.

1. **Validate volume pricing and quotation presentation.** Confirm the approved
   B2B pricing rules against existing tiers and category discounts. If a gap is
   confirmed, implement it through shared server-side pricing and test tier
   boundaries and revised quotations.
2. **Author real curated collections.** The collection infrastructure is
   implemented; create and verify useful product kits for chosen sectors,
   with factual descriptions, suitable products and accurate metadata.
3. **Consider local landing pages only for verified offerings.** If the
   business confirms locations and services, use an allowlisted, maintainable
   page model with genuinely local copy, accurate claims, canonical metadata,
   internal links and sitemap coverage. Avoid thin or duplicated pages.
4. **Create an authentic review-request process.** Invite customers to leave
   honest reviews without incentives, gating or pressure. Do not treat a target
   review count as a promised SEO result.

## 5. Priority 2 — operations and maintenance

1. **Harden releases.** Define required CI gates, staging verification,
   migration sequencing, health checks and recovery/rollback steps for the
   `production` deployment path.
2. **Document production operations.** Verify database, queue workers,
   scheduler, storage, mail, Stripe webhook registration, monitoring, backups
   and restore procedures in the actual deployment environment.
3. **Define account data lifecycle.** Decide whether deletion or
   anonymization is required, with order-retention and accounting obligations
   considered, then implement an authorized process.
4. **Decide on scheduled NewWave synchronization.** Full catalogue sync is
   not currently scheduled. Keep manual dispatch unless a defined operational
   need justifies a scheduler and its monitoring/retry policy.
5. **Add performance baselines when warranted.** Prefer workload-based
   measurements for high-traffic paths. Keep feed queries bounded as catalogue
   size grows.

## 6. Implementation and release checks

- For each behavior change, add or update focused Pest coverage and run the
  affected tests. Do not weaken existing assertions to get a green result.
- Before completion, use the validation commands in [AGENTS.md](./AGENTS.md);
  resolve new high-severity Sloppy findings and PHPStan errors.
- When a Blade, CSS, JavaScript or other frontend build input changes, run
  `npm run build` and include generated `public/build` assets with the change.
- A green test/build does not by itself establish production readiness,
  payment-provider configuration, legal compliance or recoverability.
