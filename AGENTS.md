# OpenCode Agent Instructions

## Project Overview

**Abbigliamento Personalizzato** - Custom Apparel E-commerce Platform

- **Purpose**: Quote-based custom apparel ordering system (no online payments)
- **Target Users**: Customers browse catalog -> create quotes -> admin processes manually
- **Tech Stack**: Laravel 13, Livewire 4, Filament 5, Tailwind CSS 4, Spatie Media Library 11

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

- Filament: Use Get  for conditional visibility, Set  for reactive updates
- Livewire: Use ->live(onBlur: true) on text inputs
- Vite error: Run npm run build if "Unable to locate file in Vite manifest"

## Testing

- Always ->actingAs(User::factory()->create()) before testing panel functionality
- Edit pages: pass ['record' => ->id], use ->call('save'), not ->assertRedirect()

## Skills Activation

- fortify-development - Authentication
- laravel-best-practices - Backend PHP
- fluxui-development - Flux UI in Livewire
- livewire-development - Livewire components
- pest-testing - Pest tests
- tailwindcss-development - Tailwind CSS
- medialibrary-development - Spatie media
- debug-using-debugbar - Debugbar

## MCP Tools

- database-query - Read-only SQL
- database-schema - Table structure
- get-absolute-url - Resolve URLs
- browser-logs - Frontend logs
- search-docs - Laravel docs (ALWAYS use before coding)

Last Updated: April 2026