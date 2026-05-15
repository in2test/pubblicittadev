# 📋 Piano di Implementazione - Abbigliamento Personalizzato

**Status**: 🚧 IN CORSO (Fase 2: Transizione E-commerce)
**Scadenza MVP**: Raggiunta
**Ultimo aggiornamento**: 15 Maggio 2026

---

## 📊 Panoramica Progetto

**Nome**: Piattaforma di E-commerce per stampe personalizzate su abbigliamento
**Focus**: Abbigliamento (Workwear - Basic Hoody, Basic Roundneck, etc.)
**Flusso**: Quote-based (No pagamento online) → Ordini manuali → Admin gestisce
**Lingua**: Italiano
**Team**: 1 developer
**Timeline**: 4 settimane (Fase 1 + Fase 2 in corso)

---

## 🗄️ Struttura Database (Aggiornata)

Abbiamo adottato un sistema di **Varianti Prodotto** più flessibile invece di semplici tabelle piatte.

### Tabelle Principali

```
📦 products
├── id, name, slug, sku, description, price (base), category_id, is_featured
├── type (standard|newwave), sync_status, sync_progress, synced_at
├── override_price, override_description, disabled_colors (JSON), remote_images (JSON)
├── is_active, created_at, updated_at

📦 categories
├── id, name, slug, parent_id, description

📦 colors
├── id, color_name, color_hex, color_code, sort_order

📦 sizes
├── id, name, code, size_code, sort_order

📦 images (NUOVO - Cache immagini remote e locali)
├── id, product_id, image_url, thumbnail_url, medium_url, large_url
├── color_id (nullable), order_by, image_description, alt

📦 print_placements (es. Petto, Schiena, Manica)
├── id, name

📦 product_print_placement (Pivot con Meta)
├── product_id, print_placement_id, additional_price

📦 print_sides (es. Fronte, Retro, Sinistra, Destra)
├── id, name

📦 product_variations (Pivot Centrale)
├── id, product_id, color_id, size_id, print_placement_id, print_side_id
├── sku, quantity, is_available

📦 pricing_tiers
├── id, product_id, min_quantity, max_quantity, price_per_unit

📦 category_quantity_discounts (Sconti quantità per categoria)
├── id, category_id, min_quantity, max_quantity
├── discount_type (percent|fixed), discount_value, description

📦 customization_points
├── id, name, category, description, display_order

📦 quotes (Preventivi / Pre-ordini)
├── id, user_id (FK), quote_number, customer_name, customer_email, customer_phone, customer_whatsapp
├── total_items, total_price, status, notes

📦 quote_items
├── id, quote_id, product_id, color_id, quantity, unit_price, subtotal
├── customization_json (JSON con le opzioni selezionate)
├── design_file_path (Percorso file caricato)

📦 addresses (NUOVO - Gestione Indirizzi Utente)
├── id, user_id (FK), type (shipping|billing), name, street, city, state, zip, country, phone, is_default
```

---

## 🎯 Funzionalità Implementate

### ✅ SETTIMANA 1-2: Fondamenta & MVP (COMPLETATA)

- [x] **Core Backend**: Migrazioni, Models, Relazioni e Seeders.
- [x] **Catalogo**: Vista prodotti, categorie e dettaglio prodotto.
- [x] **Carrello Base**: Logica di aggiunta, calcolo prezzi e sconti quantità.
- [x] **Preventivi**: Form di richiesta con upload file design.
- [x] **Admin Panel**: Risorse Filament per prodotti, categorie e sconti.

---

### 🚀 SETTIMANA 3-4: Advanced Sync & Gallery Management (COMPLETATA)

#### 🌐 Integrazione API NewWave Avanzata
- [x] **Authenticated GraphQL**: Integrazione sicura con il gateway NewWave (SSL bypassato per dev).
- [x] **Lazy-Sync**: Controllo automatico ogni 12 ore della freschezza dei dati via middleware/controller.
- [x] **Fast Availability Sync**: Metodo dedicato per aggiornare solo le giacenze senza re-importare l'intero prodotto.
- [x] **Inventory Scaling**: Logica di protezione inventario (`floor(availability / 2)`) per mappare stock reale.
- [x] **CDN-Mode**: Sincronizzazione automatizzata delle immagini remote (lifestyle e varianti).

#### 🖼️ Gestione Media & Gallery
- [x] **Hybrid Gallery Engine**: Sistema che unifica immagini locali (Spatie MediaLibrary) e remote (Images table).
- [x] **Filtro Colore Intelligente**: La gallery mostra solo le immagini associate al colore selezionato (o le lifestyle).
- [x] **Admin Gallery Control**: Interfaccia per reordering manuale e override delle associazioni colore per immagini API.
- [x] **Thumbnails on Hover**: Visualizzazione anteprima prodotto nelle tabelle admin di Filament.

#### 🛒 Lavorazioni (Job-Based Cart)
- [x] **Job UUID**: Ogni aggiunta al carrello è trattata come una "Lavorazione" (Job) unica con UUID.
- [x] **Recalculation logic**: Sconti quantità applicati alla singola lavorazione per garantire precisione sui prezzi personalizzati.
- [x] **Size Selector UI**: Interfaccia avanzata per selezione quantità multi-taglia con feedback "Total Articles".

### 👤 Modulo 2: User Lifecycle & Sicurezza (COMPLETATO)
- [x] **User Management**: Ruoli (admin/client) e is_active.
- [x] **Fortify Email Verification**: Obbligatoria per accedere alla dashboard.
- [x] **Auth UI**: Modal Login/Register moderno (Refactored to Minimal Alpine/Livewire).
- [x] **Codebase Stability**: Risolti errori Facade instantiation (new Storage, etc.) e bug static analysis.
- [x] **Dashboard Cliente**: Visualizzazione cronologia preventivi associati.
- [x] **Address Manager**: CRUD indirizzi in Livewire Volt + Flux UI.

---

## 📅 Checkpoint Aggiornati

```
MAGGIO 2026 (Stato Attuale)
├─ ✅ NewWave Sync v2.0 (Lazy-sync + Availability)
├─ ✅ Product Gallery v2.0 (Color mapping + Reordering)
├─ ✅ Job-Based Cart (Lavorazioni differenziate)
├─ ✅ User Dashboard & Address Manager (Flux UI)
├─ ✅ Stripe Checkout & Webhooks
├─ ✅ Inventory Management (Auto-decrement on payment)
├─ ✅ Admin Order Management (Filament Resource)
└─ ✅ Test Suite (145+ test Pest)

PIANO PROSSIMI GIORNI
├─ 🚧 Email Notifications (Conferma ordine - Basic implemented)
├─ 🚧 Invoice Generation (PDF invoices)
└─ 🚧 Shipping Tracking integration
```

---

## 🧪 Test Suite

Creati **145 test** (Pest) che coprono:
- `CartTest`, `SearchTest`, `ProductPageTest`, `QuoteTest`, `QuantityDiscountServiceTest`.
- Nuovi test per `ProductSynchronizer` e `NwgApiClient`.

---

## ⏭️ PHASE 2: Full E-commerce Transition

### 📦 Modulo 1: L'Engine delle "Lavorazioni" (COMPLETATO)
- [x] Refactoring del `CartManager` (Job-based).
- [x] Logica Sconti per Lavorazione.
- [x] Implementazione funzione "Modifica Lavorazione" nel carrello.

### 👤 Modulo 2: User Lifecycle & Sicurezza (COMPLETATO)
- [x] User Management con ruoli (admin/client) e is_active.
- [x] Filament UserResource.
- [x] Login/Register modal Livewire (Minimal Alpine/Livewire Refactor).
- [x] Configurazione Fortify (Verifica Email).
- [x] Area "I miei Ordini" e gestione indirizzi.

### 💳 Modulo 3: Pagamenti & Checkout (Stripe) (COMPLETATO)
- [x] Integrazione Stripe Checkout.
- [x] Stripe Webhooks per creazione ordine.
- [x] Incremento/Decremento automatico inventario al pagamento.

### 🛠️ Modulo 4: Admin Order Management (IN CORSO)
- [x] `OrderResource` in Filament.
- [ ] Gestione Fatture & Tracking Spedizioni.
- [x] Sistema Notifiche Email automatiche (Basic Paid Confirmation).

---

## 🚨 Rischi & Mitigation

| Rischio                     | Mitigation                                                   |
| --------------------------- | ------------------------------------------------------------ |
| API Rate Limiting (NewWave) | Implementato caching aggressivo e Fast Availability Sync     |
| Integrità Dati Immagini     | Validazione URL e fallback automatico a placeholder          |
| Performance Carrello        | Ottimizzazione query `getPriceForQuantity` con eager loading |

---

🚀 **TARGET LIVE**: Fine Maggio 2026