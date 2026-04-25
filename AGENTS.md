# OpenCode Agent Instructions

## Project Overview

**Abbigliamento Personalizzato** - Custom Apparel E-commerce Platform

- **Purpose**: Quote-based custom apparel ordering system (no online payments)
- **Target Users**: Customers browse catalog → create quotes → admin processes manually
- **Current Status**: MVP in progress (Week 2 - Admin Panel & Polish)
- **Tech Stack**: Laravel 13, Livewire 4, Filament 5, Tailwind CSS 4, Spatie Media Library 11

## 🗄️ Database Schema

### **Core Tables**

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | Authentication | id, name, email, password, email_verified_at |
| `categories` | Product categorization | id, name, slug, parent_id, description |
| `products` | Product catalog | id, name, slug, description, sku, price, category_id, is_featured, type, sync_status |
| `colors` | Color options | id, color_name, color_hex, color_code, sort_order |
| `sizes` | Size options | id, size_name, size, sort_order |
| `print_placements` | Print locations | id, name, description, sort_order, default_price |
| `print_sides` | Print sides | id, name, description, sort_order |
| `product_variations` | **Central pivot** | id, product_id, color_id, size_id, print_placement_id, print_side_id, sku, quantity, is_available |
| `pricing_tiers` | Quantity pricing | id, product_id, min_quantity, max_quantity, price_per_unit |
| `quotes` | Customer quotes | id, quote_number, customer_name, customer_email, total_items, total_price, status |
| `quote_items` | Quote line items | id, quote_id, product_id, color_id, quantity, unit_price, customization_json, design_file_path |
| `images` | Product images | id, product_id, image_path, sort_order, is_primary |
| `media` | Spatie Media Library | Standard Laravel media table |

### **Key Relationships**

- **ProductVariation** is the central pivot linking: Product → Color → Size → PrintPlacement → PrintSide
- **PricingTier** enables tiered pricing based on quantity
- **Quote** system is quote-based (no online payment)

## Core Philosophy

- **Signal over Noise**: Include only high-signal, repo-specific details. Exclude generic advice or obvious framework conventions (e.g., "always use PHPDoc" is generic).
- **Verifiability**: Must be something an agent could miss without explicit help (e.g., a non-obvious command sequence, a key architectural constraint, a specific configuration quirk).
- **Source of Truth**: Trust executable configuration files (`*config`, `*rules`) and CI/pre-commit hooks over prose documentation.

## 💡 Investigation Workflow (How to Investigate)

1.  **Read Order Hierarchy**: Check in this order:
    - `README*`, root manifests (`composer.json`, `package.json`)
    - Build/Test/Lint/Format/Typecheck/CodeGen configs (e.g., `phpunit.xml`, `tailwind.config.js`).
    - CI workflows and pre-commit hooks (`.github/workflows/*.yml`, `.git/hooks/*`).
    - Existing instruction files (`AGENTS.md`, `laravel-best-practices`, etc.).
2.  **Deep Dive**: If the architecture is unclear after config review, inspect a small, representative set of files focusing on **execution flow, boundaries, and wiring logic** (e.g., service providers, middleware definitions).
3.  **Conflict Resolution**: If docs conflict with config/scripts, _trust the executable source_ (config/scripts) and document that discrepancy.

## 🎯 Information to Extract (What is High Signal?)

- **Exact Commands**: Non-obvious, specific CLI commands (e.g., `php artisan cache:clear` vs. `php artisan config:clear`).
- **Step Ordering**: Required sequences (e.g., `lint -> typecheck -> test` must be in order).
- **Architectural Boundaries**: Define package ownership, primary entrypoints, and which systems communicate directly.
- **Framework Quirks**: State non-default behaviors (e.g., required global middleware, specific data fetching patterns).
- **Testing Quirks**: Mention necessary fixtures, setup prerequisites, or known flaky tests that need skipping/special handling.

## ❓ Asking Questions

Use the `?` tool **only** if repository evidence is exhausted and the following is missing:

- Undocumented team conventions.
- Branch/PR/release guidelines.
- Missing setup prerequisites known to the team.

## ✍️ Writing Rules Summary

- **Include**: Specific commands, architectural shortcuts, deviation from defaults.
- **Exclude**: Generic advice, basic concepts (e.g., "use braces"), or anything that is too speculative.
- **Style**: Use short, bulleted lists. If the repo is large, summarize _only_ the structural facts that change agent workflow.

**Example Detail to Preserve**: When editing Filament components, always use `->visibility(fn (Get $get): bool => $get('type') === 'business')` for conditional logic.
_Last Updated: [Date of completion]_

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.
- `medialibrary-development` — Build and work with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths.
- `debug-using-debugbar` — Use this skill to optimize requests or debug Laravel application issues — slow pages, N+1 queries, exceptions, failed requests, or unexpected behavior — by inspecting data captured by Laravel Debugbar via Artisan CLI commands. Use when the user asks to investigate a bug, diagnose a slow request, find duplicate queries, check what happened on a previous request, or optimize database performance, even if they don't explicitly mention "debugbar" or "profiling."

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## 🏗️ Architecture & Key Patterns

### **Core Business Flow**

1. **Catalog Browsing**: Customers browse products by category → select variations (color/size)
2. **Quote Creation**: User fills customization options → system calculates price via `pricing_tiers`
3. **Quote Submission**: Quote created with unique number (QT-YYYYMMDD-XXX) → status `pending`
4. **Admin Processing**: Admin reviews quote → accepts/rejects → updates status
5. **Production**: Accepted quotes move to production workflow

### **Product Types**

- **Standard Products**: Manually created via Filament
- **NewWave Products**: Synced from external NWG GraphQL API

### **Central Pivot: ProductVariation**

The `product_variations` table is the heart of the system:
```
ProductVariation {
  product_id, color_id, size_id, print_placement_id, print_side_id,
  sku, quantity, is_available
}
```

Links: Product → Color → Size → PrintPlacement → PrintSide

### **Pricing Tiers**

Tiered pricing based on quantity ranges:
```
PricingTier {
  product_id, min_quantity, max_quantity, price_per_unit
}
```

### **Quote System**

Quote-based (no online payments):
- `quotes` table: customer info, total items, total price, status
- `quote_items` table: line items with customization JSON, design file path

### **Media Library Setup**

- Collections: `images` on Product and Category models
- Conversions: `thumbnail` (150x150), `medium` (600x600), `large` (1000x1000)
- Automatic cleanup via `registerMediaConversions()`

### **External Integration**

- **NewWave GraphQL API**: Product sync via `ProductAvailabilityService`
- **Job**: `SyncNewWaveProductJob` handles sync workflow
- **Status Tracking**: `sync_status` enum (`pending` → `syncing` → `synced`/`failed`)

## 🔄 Architectural Patterns

### **Quote Flow**

1. User selects product → category → variations
2. User fills customization options
3. User uploads design file (optional)
4. System calculates price based on `pricing_tiers`
5. Quote created with unique number (QT-YYYYMMDD-XXX)
6. Admin manually processes quote

### **Product Sync Pattern**

1. Admin marks product for sync in Filament
2. Job queued with `SyncNewWaveProductJob`
3. Job fetches data from NWG GraphQL API
4. Product updated with new data
5. Status tracked via `sync_status` enum

### **Pricing Tiers**

- Tiered pricing based on quantity ranges
- Automatic tier selection in `QuoteController@store`
- Override prices supported via `override_price` field

## 📝 Documentation Links

- [PIANO_IMPLEMENTAZIONE.md](../PIANO_IMPLEMENTAZIONE.md) - Implementation plan
- [README.md](../README.md) - Project overview

## 🛠️ Tools & Utilities

### **Artisan Commands**
```bash
php artisan make:model,controller,migration,job,service,command
php artisan migrate,seed,queue:listen,queue:work
php artisan test,lint,cache:clear,config:clear
php artisan route:list,queue:failed,queue:flush
```

### **Code Quality**
- **Pint**: Code formatter (`vendor/bin/pint`)
- **Rector**: Automated refactoring (`vendor/bin/rector`)
- **Boost**: Laravel enhancements (`vendor/bin/boost`)

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

### **Test Patterns**

- **Feature Tests**: Use `RefreshDatabase` trait for in-memory SQLite testing
- **Browser Tests**: Use `visit()`, `click()`, `fill()` for Livewire component testing
- **Datasets**: Use factories with custom states before manually setting up models
- **Faker**: Use `$this->faker->word()` or `fake()->randomDigit()`

### **Test Commands**

```bash
php artisan test              # Run all tests
php artisan test --compact    # Compact output
php artisan test --filter=... # Filter tests
```

### **Common Pitfalls**

- **N+1 Queries**: Use `with()` eager loading in models
- **Lazy Loading**: Use `Model::preventLazyLoading()` in service provider
- **File Visibility**: Always use `->visibility('public')` for Filament resources
- **Grid Layout**: Always specify `->columnSpan()` or `->columnSpanFull()` for Grid children
- **Reactive Fields**: Use `->live(onBlur: true)` to avoid per-keystroke updates
- **Dehydrated Fields**: Never add `->dehydrated(false)` to fields that need saving

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

## 📊 Current Status

### **MVP Timeline**
- **Week 1**: ✅ Database, Models, Frontend Catalog, Quote Flow
- **Week 2**: 🏗️ Admin Panel (Filament Resources), Polish

### **Pending Items**
- Complete Filament resources for all entities
- Polish UI/UX with Flux components
- Add more product seed data
- Implement product sync workflow

## 📋 Routes Reference

### **web.php**
```php
GET  /                    -> HomePageController@index (home)
GET  /catalogo            -> CategoryController@index (catalog)
GET  /catalogo/{category} -> CategoryController@show
GET  /catalogo/{category}/{slug} -> ProductController@show
POST /quote               -> QuoteController@store
GET  /services            -> services view
GET  /contact             -> contact view
GET  /cart                -> cart view
GET  /palette             -> palette view
```

### **Auth Routes** (Fortify)
- `/login`, `/register`, `/forgot-password`, `/reset-password`, etc.

## 🛠️ Tools & Utilities

### **Artisan Commands**
```bash
php artisan make:model,controller,migration,job,service,command
php artisan migrate,seed,queue:listen,queue:work
php artisan test,lint,cache:clear,config:clear
php artisan route:list,queue:failed,queue:flush
```

### **Code Quality**
- **Pint**: Code formatter (`vendor/bin/pint`)
- **Rector**: Automated refactoring (`vendor/bin/rector`)
- **Boost**: Laravel enhancements (`vendor/bin/boost`)

## 📝 Documentation Links

- [PIANO_IMPLEMENTAZIONE.md](../PIANO_IMPLEMENTAZIONE.md) - Implementation plan
- [README.md](../README.md) - Project overview

## 🎯 Skills & Best Practices

### **Active Skills**
- `laravel-best-practices`: For all Laravel PHP code
- `pest-testing`: For test writing
- `livewire-development`: For Livewire components
- `fluxui-development`: For Flux UI components
- `medialibrary-development`: For media library
- `fortify-development`: For authentication
- `tailwindcss-development`: For Tailwind styling
- `debug-using-debugbar`: For debugging

### **Key Guidelines**
- Always use `search-docs` before making changes
- Follow existing code conventions
- Write tests for every change
- Run `pint` to format code
- Use Boost tools over manual alternatives

</laravel-boost-guidelines>
