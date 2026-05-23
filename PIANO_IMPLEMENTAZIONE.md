# 📋 Piano di Implementazione - Abbigliamento Personalizzato

**Status**: 🚧 IN CORSO (Fase 2: Transizione E-commerce)
**Scadenza MVP**: Raggiunta
**Ultimo aggiornamento**: 23 Maggio 2026

---

## 📊 Panoramica Progetto

**Nome**: Piattaforma di E-commerce per stampe personalizzate su abbigliamento e prodotti standard (es. Forex, Biglietti da Visita)
**Focus**: E-commerce Completo
**Flusso**: Acquisto diretto nel Carrello → Pagamento (Stripe/Bank) → Gestione Ordini (Lavorazioni)
**Lingua**: Italiano
**Team**: 1 developer
**Timeline**: 4 settimane (Fase 1 + Fase 2 in corso)

---

## 🗄️ Struttura Database (Unificata & Pulita)

Il database è stato ottimizzato e le migrazioni sono state **unificate** (una per ogni modello base), con rimozione completa del vecchio sistema a preventivi (quotes).

### Tabelle Principali

```
📦 products
├── id, name, slug, sku, description, category_id, is_featured
├── type (standard|newwave), pricing_model (fixed|quantity|area), min_area
├── max_width, max_height, sheet_width, sheet_height, allows_custom_size
├── min_custom_width, max_custom_width, min_custom_height, max_custom_height
├── sync_status, sync_progress, synced_at, is_active
├── override_price, override_description, remote_images (JSON)
├── price, offer_price, created_at, updated_at

📦 categories
├── id, name, slug, parent_id, description, is_active, display_mode

📦 colors
├── id, color_name, color_hex, color_code, sort_order

📦 sizes
├── id, name, code, size_code, sort_order

📦 media (Spatie Media Library)
├── id, model_type, model_id, file_name, mime_type, custom_properties, responsive_images, ecc.

📦 print_placements (es. Petto, Schiena, Manica)
├── id, name, template_path, default_price

📦 print_sides (es. Fronte, Retro, Sinistra, Destra)
├── id, name, template_path

📦 pricing_tiers (Matrice prezzi e quantità)
├── id, product_id, print_side_id, is_custom_price, min_quantity, max_quantity, price_per_unit

📦 category_quantity_discounts (Sconti quantità per categoria)
├── id, category_id, min_quantity, max_quantity
├── discount_type (percent|fixed), discount_value, description

📦 addresses (Gestione Indirizzi Utente)
├── id, user_id (FK), type (shipping|billing), name, street, city, state, zip, country, phone
├── vat_number, fiscal_code, sdi_code, pec_email, is_default

📦 orders (Ordini Ecommerce)
├── id, user_id (FK), order_number, payment_status, work_status
├── total_price, total_items, shipping_address_id, billing_address_id
├── stripe_session_id, stripe_payment_intent_id, paid_at, notes

📦 order_items (Singoli elementi dell'ordine / Lavorazioni)
├── id, order_id, product_id, quantity, unit_price, subtotal
├── customization_json (JSON con le opzioni selezionate)
├── design_file_path (Percorso file caricato)
├── work_status
```

---

## 🎯 Funzionalità Implementate

### ✅ SETTIMANA 1-2: Fondamenta & MVP (COMPLETATA)
- [x] **Core Backend**: Migrazioni, Models, Relazioni e Seeders.
- [x] **Catalogo**: Vista prodotti, categorie e dettaglio prodotto.
- [x] **Carrello Base**: Logica di aggiunta, calcolo prezzi e sconti quantità.
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

#### 🛒 Lavorazioni (Job-Based Cart) e Prodotti Standard
- [x] **Job UUID**: Ogni aggiunta al carrello è trattata come una "Lavorazione" (Job) unica con UUID.
- [x] **Configuratore Prodotti Standard**: Sviluppo form avanzato in Livewire per i prodotti standard (matrice combinazioni `print_sides` x `pricing_tiers`, configurazione dimensioni personalizzate e calcolo ritaglio fogli ottimali - `itemsPerSheet`).
- [x] **Recalculation logic**: Sconti quantità applicati alla singola lavorazione per garantire precisione sui prezzi personalizzati.
- [x] **Size Selector UI**: Interfaccia avanzata per selezione quantità multi-taglia con feedback "Total Articles".

### 👤 Modulo 2: User Lifecycle & Sicurezza (COMPLETATO)
- [x] **User Management**: Ruoli (admin/client) e is_active.
- [x] **Fortify Email Verification**: Obbligatoria per accedere alla dashboard.
- [x] **Auth UI**: Modal Login/Register moderno (Refactored to Minimal Alpine/Livewire).
- [x] **Codebase Stability**: Risolti errori Facade instantiation (new Storage, etc.) e bug static analysis.
- [x] **Dashboard Cliente**: Visualizzazione cronologia ordini e configuratore completato.
- [x] **Address Manager**: CRUD indirizzi in Livewire Volt + Flux UI (inclusa fatturazione elettronica e IVA).

---

## 📅 Checkpoint Aggiornati

```
MAGGIO 2026 (Stato Attuale)
├─ ✅ Rimozione logica preventiva (Quote-based flow abolito)
├─ ✅ Unificazione Migrations (1 migration = 1 schema)
├─ ✅ Laravel Code Simplification (Codice commentato in ITA su modelli e carrello)
├─ ✅ NewWave Sync v2.0 (Lazy-sync + Availability)
├─ ✅ Product Gallery v2.0 (Color mapping + Reordering)
├─ ✅ Job-Based Cart (Lavorazioni differenziate + formati custom)
├─ ✅ User Dashboard & Address Manager (Flux UI)
├─ ✅ Stripe Checkout & Webhooks
├─ ✅ Inventory Management (Auto-decrement on payment)
├─ ✅ Admin Order Management (Filament Resource)
├─ ✅ Pricing Models (Fixed, Quantity, Area-based)
├─ ✅ Refactoring Prodotti Standard (Filament 2-Column Form, Prod. Table)
└─ ✅ Test Suite (170+ test Pest, 100% Passing)

PIANO PROSSIMI GIORNI
├─ 🚧 Email Notifications (Conferma ordine)
├─ 🚧 Invoice Generation (PDF invoices)
└─ 🚧 Shipping Tracking integration
```

---

## 🧪 Test Suite

Creati oltre **170 test** (Pest) che coprono:
- `CartTest`, `SearchTest`, `ProductPageTest`, `OrderTest`, `QuantityDiscountServiceTest`.
- Nuovi test per `ProductSynchronizer` e `NwgApiClient`.
- Copertura formati custom e calcoli di ridimensionamento (`StandardProductResourceTest`).

---

## ⏭️ PHASE 2: Full E-commerce Transition

### 📦 Modulo 1: L'Engine delle "Lavorazioni" (COMPLETATO)
- [x] Refactoring del `CartManager` (Job-based).
- [x] Logica Sconti per Lavorazione e prezzario al metro quadro.
- [x] Implementazione form per prodotti senza varianti taglia/colore, ma basati su preventivo matrice righe.
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