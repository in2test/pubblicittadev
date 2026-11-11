# Agent guide

This is the short, always-on entry point for coding agents. Trust source code,
migrations, configuration and current test results over prose when they
disagree.

## Project intent

Pubblicitta24 is a catalogue and ordering platform for custom-printed apparel,
signage and related products. Customers configure products, then either pay
through Stripe Checkout or request a quotation. Both flows use `orders` and
`order_items`; a quotation is an order with `payment_status = quotation`.
Filament is used for administration and fulfilment.

## Read the right source

- [ARCHITECTURE.md](./ARCHITECTURE.md) documents domain rules, code boundaries
  and important implementation details. Read the relevant sections before
  changing those areas.
- [PIANO_IMPLEMENTAZIONE.md](./PIANO_IMPLEMENTAZIONE.md) is the working
  backlog. Confirm an item is still open against current code and tests before
  implementing it.
- Read [.ai/rules/index.md](./.ai/rules/index.md) and each matching path rule
  before editing. Load the relevant project skill when one is available in
  the active host; skills are maintained under `.github/skills/` and
  `.agents/skills/`.

## Working loop

1. Inspect the target code, adjacent tests, configuration and current worktree
   changes. Preserve unrelated work.
2. For Laravel ecosystem API changes, use Laravel Boost `search-docs` when
   available and confirm package versions in the lock files.
3. Make the smallest complete change. Add or adjust focused tests for behavior
   changes; never weaken existing assertions to get a green result.
4. Run the narrowest relevant tests. Format changed PHP with Pint and run the
   Sloppy diff check. For Blade, CSS, JavaScript or other frontend build
   inputs, run `npm run build`.
5. Inspect the final diff and report checks that could not be run.

## Common validation commands

```sh
php artisan test --compact
php artisan test --compact --filter=testName
vendor/bin/pint --dirty --format agent
vendor/bin/sloppy diff master --fail-on=high
vendor/bin/phpstan analyse
npm run build
php artisan route:list --except-vendor
php artisan truss:export --format=llm --compact
```

`composer run format` runs code generators and multiple formatting and
static-analysis steps; use it deliberately and inspect generated changes.
For schema work, follow the Truss guidance in `.ai/rules` and inspect the
intended database structure before writing a migration. Production deployment
is manual; see the release model in [PIANO_IMPLEMENTAZIONE.md](./PIANO_IMPLEMENTAZIONE.md)
and do not run `deploy.sh` from a development workstation.
