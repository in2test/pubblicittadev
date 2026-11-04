# Agent guide

This file is the short, always-on entry point for coding agents. It describes
how to navigate the project, not the full architecture or current backlog.
Trust the source code, migrations, configuration and current test results over
prose when they disagree.

## Project intent

Pubblicitta24 is a catalogue and ordering platform for custom-printed apparel,
signage and related products. Customers configure products, then either pay
through Stripe Checkout or request a quotation. Both flows use `orders` and
`order_items`; a quotation is an order with `payment_status = quotation`.
Filament is used for administration and fulfilment.

## Read the right source

- [ARCHITECTURE.md](./ARCHITECTURE.md) is the durable reference for business
  vocabulary, pricing and order rules, system boundaries and where code lives.
  Read the relevant sections before changing those domains.
- [PIANO_IMPLEMENTAZIONE.md](./PIANO_IMPLEMENTAZIONE.md) is the working status
  and prioritized backlog. Confirm an item is still open against the code
  before implementing it.
- Before editing, read [.ai/rules/index.md](./.ai/rules/index.md) and every
  path-scoped rule that applies to the files in scope.
- Load the matching project skill when one is available in the active host.
  Project skill definitions are maintained under `.github/skills/` and
  `.agents/skills/`; their descriptions explain when they apply.

## Tools and skills

Available capabilities depend on the agent host. Do not assume an MCP tool or
skill is available until the host exposes it.

- **Local CLI:** Git, ripgrep, PHP/Artisan, Composer and npm are the standard
  ways to inspect and validate this repository.
- **Laravel Boost:** `boost.json` configures Boost for this project. When its
  MCP tools are available, use them for Laravel documentation search, read-only
  database queries/schema inspection, application logs, Debugbar data, browser
  logs and URL resolution.
- **Browser and GitHub tools:** use them for UI validation or repository
  operations when the active host provides them.
- **Project skills:** Laravel/domain (`laravel-best-practices`,
  `infer-conventions`, `truss-schema`, `scout-development`,
  `medialibrary-development`, `debug-using-debugbar`); UI and framework
  (`filament-development`, `livewire-development`, `volt-development`,
  `fluxui-development`, `tailwindcss-development`, `fortify-development`);
  payments (`stripe-best-practices`, `upgrade-stripe`); testing
  (`pest-testing`, `testing-best-practices`). The active host may provide
  additional skills.

## Working loop

1. Inspect the target code, adjacent tests, configuration and current changes.
   Avoid broad repo-wide orientation when the task is local.
2. For Laravel ecosystem API changes, use Boost `search-docs` when available;
   verify package versions in the lock files.
3. Make the smallest complete change. Add or adjust tests for behavior changes;
   never weaken an existing test to make it pass.
4. Run the narrowest relevant tests. For PHP changes, format with Pint and run
   the Sloppy diff check. For Blade, CSS, JavaScript or other frontend build
   inputs, run `npm run build`.
5. Inspect the final diff and preserve unrelated worktree changes.

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

`composer run format` runs code generators and multiple format/static-analysis
steps; use it deliberately and inspect its generated changes. For focused
changes, prefer the narrow commands above.
