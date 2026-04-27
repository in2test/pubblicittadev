# OpenCode Agent Instructions

## Project Overview

**Abbigliamento Personalizzato** - Custom Apparel E-commerce Platform

- **Purpose**: Quote-based custom apparel ordering system (no online payments)
- **Target Users**: Customers browse catalog -> create quotes -> admin processes manually
- **Tech Stack**: Laravel 13, Livewire 4, Filament 5, Tailwind CSS 4, Spatie Media Library 11, Laravel Scout 11

## Investigation Workflow

1. Read README*, composer.json, package.json first
2. Check build/test/lint configs (phpunit.xml, tailwind.config.js)
3. Check CI workflows (.github/workflows/*.yml)
4. Trust config files over prose documentation

## Commands

- **Format code**: vendor/bin/pint --dirty --format agent
- **Run tests**: php artisan test --compact or php artisan test --compact --filter=testName
- **Clear cache**: php artisan cache:clear vs php artisan config:clear
- **List routes**: php artisan route:list --method=GET --path=api

## Framework Quirks

- Filament: Use Get for conditional visibility, Set for reactive updates
- Livewire: Use ->live(onBlur: true) on text inputs
- Vite error: Run npm run build if "Unable to locate file in Vite manifest"

## Testing

- Always ->actingAs(User::factory()->create()) before testing panel functionality
- Edit pages: pass ['record' => $id], use ->call('save'), not ->assertRedirect()

## Skills Activation

- fortify-development - Authentication
- laravel-best-practices - Backend PHP
- fluxui-development - Flux UI in Livewire
- livewire-development - Livewire components
- pest-testing - Pest tests
- tailwindcss-development - Tailwind CSS
- medialibrary-development - Spatie media
- debug-using-debugbar - Debugbar
- scout-development - Search with Laravel Scout

## MCP Tools

- database-query - Read-only SQL
- database-schema - Table structure
- get-absolute-url - Resolve URLs
- browser-logs - Frontend logs
- search-docs - Laravel docs (ALWAYS use before coding)

## Critical Patterns

### Laravel Scout (v11)

When using Scout's `query()` method for eager loading, the callback receives `Illuminate\Database\Eloquent\Builder`, NOT `Laravel\Scout\Builder`:

```php
// CORRECT - no type hint or use Eloquent Builder
Product::search('query')
    ->query(fn ($query) => $query->with(['category', 'media']))
    ->get();

// WRONG - causes TypeError at runtime
Product::search('query')
    ->query(fn (Builder $query) => $query->with(['category', 'media'])) // $query is Eloquent Builder, not Scout Builder
    ->get();
```

Scout database driver uses LIKE/full-text queries on your actual database tables - no external indexing needed.

Last Updated: April 2026
