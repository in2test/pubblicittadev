# 📋 Piano di Implementazione - Abbigliamento Personalizzato

**Status**: ✅ COMPLETATO (MVP Core)
**Scadenza MVP**: Raggiunta
**Ultimo aggiornamento**: 6 Maggio 2026

---

## 📊 Panoramica Progetto

**Nome**: Plataforma di E-commerce per stampe personalizzate su abbigliamento
**MVP Focus**: Abbigliamento (Workwear - Basic Hoody, Basic Roundneck, etc.)
**Flusso**: Quote-based (No pagamento online) → Ordini manuali → Admin gestisce
**Lingua**: Italiano
**Team**: 1 developer
**Timeline**: 2 settimane

---

## 🗄️ Struttura Database (Aggiornata)

Abbiamo adottato un sistema di **Varianti Prodotto** più flessibile invece di semplici tabelle piatte.

### Tabelle Principali

```
📦 products
├── id, name, slug, description, price (base), category_id, is_featured
├── is_active, created_at, updated_at

📦 categories
├── id, name, slug, parent_id, description

📦 colors
├── id, color_name, color_hex, color_code, sort_order

📦 sizes
├── id, name, code, sort_order

📦 print_placements (es. Petto, Schiena, Manica)
├── id, name

📦 print_sides (es. Fronte, Retro, Sinistra, Destra)
├── id, name

📦 product_variations (Pivot Centrale)
├── id, product_id, color_id, size_id, print_placement_id, print_side_id
├── sku, quantity, is_available

📦 pricing_tiers
├── id, product_id, min_quantity, max_quantity, price_per_unit

📦 category_quantity_discounts (NUOVO - Sconti quantità per categoria)
├── id, category_id, min_quantity, max_quantity
├── discount_type (percent|fixed), discount_value, description

📦 customization_points
├── id, name, category, description, display_order

📦 quotes (Preventivi)
├── id, quote_number, customer_name, customer_email, customer_phone, customer_whatsapp
├── total_items, total_price, status, notes

📦 quote_items
├── id, quote_id, product_id, color_id, quantity, unit_price, subtotal
├── customization_json (JSON con le opzioni selezionate)
├── design_file_path (Percorso file caricato)
```

---

## 🎯 Funzionalità Implementate

### ✅ SETTIMANA 1: Fondamenta & Core Backend (COMPLETATA)

#### Giorni 1-2: Setup Database & Models

- [x] Creare migrazioni (products, categories, colors, sizes, variations, pricing_tiers, quotes, category_quantity_discounts)
- [x] Generare Models: `Product`, `Category`, `Color`, `Size`, `ProductVariation`, `PricingTier`, `Quote`, `QuoteItem`, `CategoryQuantityDiscount`
- [x] Setup relazioni Eloquent (Focus su `ProductVariation` come pivot)
- [x] Seed database con prodotti workwear e varianti

**✅ MILESTONE 1**: Database strutturato con sistema varianti avanzato.

#### Giorni 3-5: Frontend - Catalogo & Richiesta Preventivo

- [x] Creare vista catalogo e dettaglio prodotto (`ProductController`)
- [x] Rotte dinamiche per categorie e prodotti
- [x] Implementazione di Galleria Prodotto interattiva con thumbnail e navigazione
- [x] Logica di switch colori in tempo reale via query parameters
- [x] Logica di calcolo prezzo basata sui `pricing_tiers` nel `QuoteController`
- [x] Gestione upload file design nel form preventivo
- [x] Memorizzazione preventivo e relativi articoli nel DB

**✅ MILESTONE 2**: Flusso utente dalla scelta prodotto all'invio preventivo funzionante.

---

### 📱 SETTIMANA 2: Admin Panel & Polish (COMPLETATA)

#### Giorni 8-10: Admin Panel (Filament)

- [x] Creare Filament Resource: `ProductResource` (con VariationsRelationManager)
- [x] Creare Filament Resource: `CategoryResource`
- [x] Creare Filament Resource: `ColorResource`
- [x] Creare Filament Resource: `ProductVariationResource`
- [x] Creare Filament Resource: `CategoryQuantityDiscountResource` per gestione sconti quantità
- [x] Creare Filament Resource: `PrintPlacementResource` e `PrintSideResource`
- [x] Implementazione `NewWaveProductResource` e `StandardProductResource` per gestione differenziata prodotti
- [x] Batch Import via Filament modal for NewWave products (Import da SKU) with category selection and print placements
- [x] Print placements in batch import (multi-select) attached to all imported products
- [x] Gestione stati: pending, syncing, synced, failed
- [x] **Thumbnail on hover** per product name in Filament tables

**✅ MILESTONE 3**: Gestione catalogo completa da pannello admin (incluso Batch Import)

#### 🌐 Integrazione API & Dati Esterni (NUOVO)

- [x] Sistema di mapping per `remote_images` da API esterne a media locali
- [x] Integrazione GraphQL per l'importazione automatizzata prodotti NewWave
- [x] `DebugController` per testing payload API e validazione dati

#### 🔍 Ricerca (Laravel Scout) - NUOVO

- [x] Installato `laravel/scout` v11
- [x] Configurato database driver (ricerca locale, no servizi esterni)
- [x] Aggiunto `Searchable` trait al modello `Product`
- [x] Implementato `toSearchableArray()` con name, sku, description
- [x] Ricerca full-text nel catalogo (`CategoryController`)
- [x] Ricerca scoped per categoria

**⚠️ Nota Tecnica**: Il callback `query()` di Scout riceve `Illuminate\Database\Eloquent\Builder`, NON `Laravel\Scout\Builder`. Usare:
```php
Product::search('query')
    ->query(fn ($query) => $query->with(['category', 'media']))
    ->get();
```

#### 🛒 Carrello con Sconti Quantità - NUOVO

- [x] `CartManager` service per gestione session-based cart
- [x] Raggruppamento per product_id + color + size + print_placements
- [x] Merge quantity per stesso prodotto
- [x] `Product::getPriceForQuantity($qty)` con sconti da `category_quantity_discounts`
- [x] `QuantityDiscountService` per calcolo sconti (cerca su category tree)
- [x] Sconti percentuali e fissi
- [x] AJAX endpoint `/cart/price` per calcolo prezzo real-time
- [x] Filamento per visualizzazione sconti nel carrello ("Sconto del X%")

**✅ MILESTONE 4**: Sistema carrello completo con sconti quantità.

---

## 📅 Checkpoint Aggiornati

```
SETTIMANA 2 (Recap)
├─ ✅ Admin Prodotti (v0.6)
├─ ✅ Ricerca Scout (v0.7)
├─ ✅ Carrello + Sconti (v0.8)
└- ✅ Test Suite (v0.9)

SETTIMANA 3 (Apr 28, 2026)
├─ ✅ User Management (role=admin/client, is_active)
├─ ✅ Filament UserResource - manage users
├─ ✅ AuthenticateAdmin middleware - restrict Filament access
├─ ✅ Livewire AuthModal - login/register modal
├─ ✅ User dropdown menu with logout
├─ ✅ Admin menu organization (Catalogo/Configurazione/Impostazioni)
├─ ✅ SizeResource + PrintSideResource
```

---

## 🧪 Test Suite

Creati **131 test** (Pest) che coprono:

### Feature Tests
- `CartTest` - Aggiunta, update, rimozione items, merge quantità
- `CartPriceAjaxTest` - Calcolo prezzo AJAX
- `SearchTest` - Ricerca catalogo con Scout
- `ProductPageTest` - Dettaglio prodotto
- `QuoteTest` - Creazione preventivi

### Unit Tests
- `CartManagerTest` - Logica gestione carrello
- `QuantityDiscountServiceTest` - Calcolo sconti, category tree
- `ProductDiscountTest` - Applicazione sconti su prodotto

---

## 🛠️ Stack Tecnico

```
Backend: Laravel 13
Frontend: Blade + Tailwind 4 + Flux UI
Admin: Filament 5
Search: Laravel Scout 11 (database driver)
Testing: Pest 4
```

---

## ⏭️ PHASE 2: Full E-commerce Transition

L'obiettivo di questa fase è trasformare il sistema da un generatore di preventivi a una piattaforma e-commerce completa con gestione di "Lavorazioni" indipendenti e pagamenti integrati.

### 📦 Modulo 1: L'Engine delle "Lavorazioni" (Job-Based Cart)
Il sistema non raggrupperà più i prodotti genericamente, ma tratterà ogni aggiunta al carrello come una "Lavorazione" unica.
- [ ] Refactoring del `CartManager`: ogni item nel carrello è un'entità unica con proprio `customization_json` e `design_file_path`.
- [ ] Logica Sconti per Lavorazione: il `QuantityDiscountService` applica lo sconto basandosi sulla quantità del singolo Job, non sul totale dell'ordine.
- [ ] Gestione Job nel Carrello: implementazione della funzione "Modifica Lavorazione" per permettere al cliente di cambiare colori, quantità o design per ogni singolo job.
- [ ] **Sistema di Visualizzazione Dinamica (Attribute-Based Gallery)**:
    - [ ] Implementazione di una gerarchia di fallback per le immagini: *Combinazione Specifica $\rightarrow$ Attributo Singolo $\rightarrow$ Prodotto Default*.
    - [ ] Gestione di attributi multipli (Materiale, Spessore, Posizionamento) per il trigger delle immagini in gallery.
    - [ la l'indirizzo di fatturazione e spedizione.
    - [ ] Logica di pricing fallback: *Prezzo Variante $\rightarrow$ Prezzo Base Prodotto*.
    - [ ] Integrazione nel pannello Admin (Filament) per mappare immagini a attributi specifici.

### 👤 Modulo 2: User Lifecycle & Sicurezza
Implementazione del sistema utenti per gestire l'area clienti e la conformità legale.
- [ ] Configurazione Laravel Fortify: Registrazione, Login, Recupero Password e **Verifica Email**.
- [ ] User Profile CRUD: gestione indirizzi di fatturazione e spedizione.
- [ ] Sezione GDPR: implementazione checkbox di consenso privacy e gestione dei dati personali.
- [ ] Area "I miei Ordini": storico ordini con stato e accesso ai documenti.

### 💳 Modulo 3: Pagamenti & Checkout (Stripe)
Transizione dal preventivo all'acquisto reale.
- [ ] Integrazione Stripe Checkout: reindirizzamento sicuro per il pagamento.
- [ ] Webhook di Stripe: gestione automatica della creazione dell'ordine dopo il pagamento (`checkout.session.completed`).
- [ ] Gestione Inventario: decremento automatico delle quantità disponibili in `product_variations` al momento del pagamento.

### 🛠️ Modulo 4: Admin Order Management & Logistica
Nuovi strumenti per l'amministratore per gestire il ciclo di vita dell'ordine.
- [ ] Filament `OrderResource`: gestione completa degli ordini e delle relative lavorazioni.
- [ ] Gestione Fatture: campo di upload per il PDF della fattura elettronica (generata esternamente).
- [ ] Logistica "Lean": campi per inserimento Codice di Tracking e URL del corriere.
- [ ] Sistema Notifiche: email automatiche per "Ordine Ricevuto", "Fattura Disponibile" e "Ordine Spedito".

### 🌐 Modulo 5: CMS & SEO
Creazione delle pagine informative e ottimizzazione per i motori di ricerca.
- [ ] Sistema Pagine Statiche: modello `Page` e Filament Resource per editare About, Services, Sustainability, FAQ, Contacts.
- [ ] SEO Tools: implementazione di `sitemap.xml` dinamico e gestione admin per il file `robots.txt`.
- [ ] Pagine Legali: Terms & Conditions e Privacy Policy.

---

## 📝 Checklist Stato Attuale (Aggiornata)

- [x] Database migrations create
- [x] Logica Varianti Prodotto (Pivot table)
- [x] Seed dati iniziali
- [x] Frontend catalogo e prodotti
- [x] Form invio preventivo con calcolo prezzi
- [x] Upload design file
- [x] Admin Prodotti/Categorie/Colori
- [x] Ricerca Laravel Scout (database driver)
- [x] Carrello con sconti quantità (da refactorizzare in Job-based)
- [x] Test suite completa
- [x] User Management con ruoli (admin/client) e is_active
- [x] Filament UserResource per gestione utenti
- [x] AuthenticateAdmin middleware
- [x] Login/Register modal Livewire
- [x] Admin menu organizzato
- [ ] **Sviluppo Fase 2: Lavorazioni & Cart Refactor**
- [ ] **Sviluppo Fase 2: User Profiles & GDPR**
- [ ] **Sviluppo Fase 2: Stripe Payment Integration**
- [ ] **Sviluppo Fase 2: Admin Order & Logistics (Invoices/Tracking)**
- [ ] **Sviluppo Fase 2: CMS Pages & SEO (Sitemap/Robots)**

## 🚨 Rischi & Mitigation

| Rischio                     | Mitigation                                                   |
| --------------------------- | ------------------------------------------------------------ |
| Upload file non funzionante | Test su staging con file grandi                              |
| Email spam folder           | Configurare SPF/DKIM correttamente                           |
| Performance lenta DB        | Aggiungere indici su quote.customer_email, quotes.created_at |
| Mobile UI rotta             | Testare su device reali, media-query Tailwind                |

---

**Note Finali**:

- Deploy incremental ogni milestone = riduce rischio breaking changes
- Ogni giorno check → se blocco, risolvere subito
- Week 2 = 50% new features + 50% testing e stabilità
- Post-launch: priorità = bug fixing e feature feedback

🚀 **TARGET LIVE**: Fine Aprile 2026