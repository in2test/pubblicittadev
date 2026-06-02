# 📋 Piano di Implementazione - Abbigliamento Personalizzato

**Status**: 🚧 IN CORSO (Fase 3: Completamento Flusso Ordini)
**Scadenza MVP**: Raggiunta
**Ultimo aggiornamento**: 27 Maggio 2026

---

## 📊 Panoramica Progetto

**Nome**: Piattaforma di E-commerce per stampe personalizzate su abbigliamento e prodotti standard (es. Forex, Biglietti da Visita)
**Focus**: E-commerce Completo con Preventivi Privati
**Flusso**: Acquisto nel Carrello → Pagamento Stripe **oppure** Richiesta Preventivo Privato → Gestione Ordini (Lavorazioni)
**Lingua**: Italiano
**Team**: 1 developer
**Timeline**: 4+ settimane (Fase 1 + Fase 2 + Fase 3 in corso)

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

📦 media (Spatie Media Library)
├── id, model_type, model_id, file_name, mime_type, custom_properties, responsive_images, ecc.

📦 pricing_tiers (Matrice prezzi e quantità)
├── id, product_id, product_sku_id, is_custom_price, min_quantity, max_quantity, price_per_unit

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
├── payment_status: pending | paid | quotation | failed | refunded

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
- [x] **Configuratore Prodotti Standard**: Sviluppo form avanzato in Livewire per i prodotti standard (configurazione varianti e pricing tiers, dimensioni personalizzate e calcolo ritaglio fogli ottimali - `itemsPerSheet`).
- [x] **Recalculation logic**: Sconti quantità applicati alla singola lavorazione per garantire precisione sui prezzi personalizzati.
- [x] **Size Selector UI**: Interfaccia avanzata per selezione quantità multi-taglia con feedback "Total Articles".

#### 🧩 Variazioni Prodotto Avanzate (NewWave)
- [x] **Tipo variazione "Quantità/Input"**: Gestione varianti multi-valore (es. 2XS: 1, L: 2) distinte dalle varianti select.
- [x] **Posizioni di Stampa**: Aggiunta selezione posizione stampa (es. petto, schiena) come variante modificatore indipendente.
- [x] **Consolidamento varianti obsolete**: Grammatura, materiale, spessore, colore tazza/braccialetto ora usano le varianti base esistenti (colore, spessore, ecc.).

### 👤 Modulo 2: User Lifecycle & Sicurezza (COMPLETATO)
- [x] **User Management**: Ruoli (admin/client) e is_active.
- [x] **Fortify Email Verification**: Obbligatoria per accedere alla dashboard.
- [x] **Auth UI**: Modal Login/Register moderno (Refactored to Minimal Alpine/Livewire).
- [x] **Codebase Stability**: Risolti errori Facade instantiation (new Storage, etc.) e bug static analysis.
- [x] **Dashboard Cliente**: Visualizzazione cronologia ordini e configuratore completato.
- [x] **Address Manager**: CRUD indirizzi in Livewire Volt + Flux UI (inclusa fatturazione elettronica e IVA).

---

### 🧾 Modulo 3: Pagamenti & Preventivi (COMPLETATO)
- [x] **Stripe Checkout**: Pagamento diretto con redirect a Stripe.
- [x] **Stripe Webhooks**: Aggiornamento automatico stato ordine al pagamento.
- [x] **Inventory Decrement**: Decremento automatico giacenze al pagamento confermato.
- [x] **Richiesta Preventivo Privato**: Bottone "Richiedi Preventivo" nel carrello per utenti autenticati (salta il checkout, crea ordine con `payment_status = quotation`).
- [x] **Pagina Successo Contestuale**: Messaggio differenziato tra "Ordine Confermato" (pagamento Stripe) e "Richiesta Inviata" (preventivo), con icone diverse.
- [x] **Email Notifiche**: Notifica automatica al cliente e all'admin sia per ordini pagati che per preventivi.

---

## 📅 Checkpoint Aggiornati

```
MAGGIO 2026 (Stato Attuale)
├─ ✅ Rimozione logica preventiva (Quote-based flow abolito, sostituito da Preventivo Privato semplificato)
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
├─ ✅ Consolidamento varianti obsolete (incorporate in varianti base)
├─ ✅ Risoluzione errori PHPStan (0 errori)
├─ ✅ Flusso Preventivo Privato dal Carrello (senza checkout)
├─ ✅ Pagina successo contestuale (preventivo vs pagamento)
└─ ✅ Test Suite (172 test Pest completati e passanti, 2 skippati)

PIANO PROSSIMI GIORNI
├─ ✅ Test e verifica di prodotti a superficie (Pannelli rigidi / Rigid panels)
├─ ✅ Test e verifica di prodotti unitari/fissi (Espositori roll-up / Roll-ups)
├─ ✅ Gestione Fatture (PDF invoices)
└─ ✅ Shipping Tracking integration

Nuovi task da analizzare:
├─ 🚧 Homepage Menu completion
└─ 🚧 Homepage Hero section redesign (carousel)
```

---

## 🧪 Test Suite

Creati oltre **172 test** (Pest) che coprono:
- `CartTest`, `SearchTest`, `ProductPageTest`, `OrderTest`, `QuantityDiscountServiceTest`.
- Nuovi test per `ProductSynchronizer` e `NwgApiClient`.
- Copertura formati custom e calcoli di ridimensionamento (`StandardProductResourceTest`).
- Test flusso preventivo privato (`CheckoutTest` – `test_direct_quotation_flow_from_cart_creates_order_and_redirects_to_success`).

---

## ⏭️ PHASE 3: Completamento & Go-Live

### 📦 Modulo 4: Admin Order Management (COMPLETATO)
- [x] `OrderResource` in Filament.
- [x] Sistema Notifiche Email automatiche (ordine pagato + richiesta preventivo).
- [x] Gestione Fatture & Tracking Spedizioni.

### 📐 Modulo 5: Verifica Tipologie di Prodotto Avanzate (COMPLETATO)
- [x] **Stampe a Superficie (Pannelli Rigidi)**: Verificare il corretto calcolo del prezzo al mq basato sulle dimensioni (larghezza x altezza in cm), rispetto dell'area minima fatturabile per pezzo e integrazione con la tabella degli sconti quantità.
- [x] **Prodotti Unitari / Fissi (Roll-Ups, Espositori)**: Verificare il comportamento del form con prezzi fissi o a scaglioni di quantità senza la selezione di taglie/colori, garantendo che le opzioni/accessori aggiuntivi vengano calcolati correttamente.

### 🛠️ Modulo 6: UI/UX Fixes & Improvements (Feedback Utente)

#### Home Page
- [ ] **Menu**: Fix inconsistent text casing (currently passes between lower/uppercase).
- [ ] **Hero Section**: Redesign the hero section (consider replacing it with a carousel).
- [ ] **Certification Links**: Add/fix certification links.
- [x] **Newsletter**: Implement/fix newsletter section.
- [x] **Footer**: Add links to maps in the footer.

#### Catalogo Page
- [x] **Spacing**: Reduce excessive space around the title and align left to match navigation.
- [x] **Filter Menu**: Fix inconsistent text casing (passes from uppercase to lowercase).
- [x] **Padding**: Unify padding of filter menu and catalog grid to match navigation (`px-6`).
- [x] **Mobile Layout**: Fix title overflow on small screens for long titles (e.g., "Abbigliamento da lavoro").

#### Product Page
- [x] **UI**: Remove "Carica il tuo design" (not needed).
- [x] **UI**: Fix overflow issue on the "Aggiungi al carrello" button.
- [ ] **Data Sourcing**: Review static text vs database-driven text for "Certifications", "Specifiche tecniche", "Caratteristiche Costruttive", and "Note per la Personalizzazione".
- [x] **Sizes**: Improve formatting of sizes for better readability.

#### Cart Page
- [x] **Mobile Layout**: Reduce excessive padding on mobile view.
- [x] **Placeholder**: Change/remove the placeholder "Istruzioni speciali per la consegna" as it makes no sense.

#### Login Page
- [x] **Error Messages**: Fix the `auth.failed` message to be more user-friendly.

#### Order Page
- [x] **UI**: Fix overflow on "ID SESSIONE:...".
- [x] **UI**: Improve contrast on the "I tuoi ordini" button.

#### Dashboard
- [x] **UI/Layout**: Fix overflow and excessive paddings on "Paga ora con stripe" and "Richiedi preventivo" buttons.
- [x] **UI**: Fix "I miei indirizzi" PLUS button placement.
- [x] **Navigation**: Re-evaluate the "Torna alla dashboard" button placement (seems out of place).
- [x] **Forms**: Change "Dati di fatturazione" to a single-column layout instead of two columns.
- [x] **Forms**: Fix the "Imposta come predefinito" checkbox (currently cannot be checked).

#### Static Pages & General
- [x] **Cookies Banner**: Implement/fix the Cookies banner.
- [ ] **Copywriting**: Rewrite texts for the "Servizi" and "Contact" static pages.
- [ ] **Legal Pages**: Create missing pages for "Informativa Privacy" and "Termini e Condizioni".
- [ ] **Portfolio**: Implement portfolio pages and an admin section to manage previous works.

---

## 🚨 Rischi & Mitigation

| Rischio                     | Mitigation                                                   |
| --------------------------- | ------------------------------------------------------------ |
| API Rate Limiting (NewWave) | Implementato caching aggressivo e Fast Availability Sync     |
| Integrità Dati Immagini     | Validazione URL e fallback automatico a placeholder          |
| Performance Carrello        | Ottimizzazione query `getPriceForQuantity` con eager loading |

---

🚀 **TARGET LIVE**: Fine Maggio 2026