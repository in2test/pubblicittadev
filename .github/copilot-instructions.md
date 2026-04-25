# Copilot Chat Instructions for Abbigliamento Personalizzato

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
