# 📋 Piano di Implementazione - Pubblicittà24 E-commerce & Local SEO

**Status**: ✅ COMPLETATO (Pronto per il Go-Live & Ottimizzazione Continua)  
**Focus**: E-commerce Completo con Pagamenti Online, Preventivi Privati & Local SEO (Fiuggi e Dintorni)  
**Ultimo aggiornamento**: 24 Luglio 2026  

---

## 📊 Panoramica Progetto

**Nome**: Piattaforma E-commerce Pubblicittà24 per stampe personalizzate, prodotti standard (Biglietti da visita, Stampa Grande Formato, Forex, Banner, Volantini) e abbigliamento promozionale/lavoro.  
**Focus**: E-commerce Completo con Pagamenti Online (Stripe) e Preventivi Privati.  
**Target Geografico**: Fiuggi, provincia di Frosinone e comuni limitrofi (Anagni, Alatri, Ferentino, Sora, Paliano, Acuto, Piglio, Guarcino, ecc.).  
**Flusso**: Acquisto nel Carrello → Pagamento Stripe **oppure** Richiesta Preventivo Privato → Gestione Ordini.  
**Tech Stack**: Laravel 13, Livewire 4, Filament 5, Volt 1, Tailwind CSS 4, Spatie Media Library 11, Laravel Scout 11.  

---

## 🗄️ Struttura Database (Unificata & Pulita)

Il database è ottimizzato e le migrazioni sono unificate, con gestione avanzata di varianti, pricing tiers, scaglioni spedizione e lavorazioni.

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
├── total_price, total_items, shipping_cost, shipping_method, shipping_address_id, billing_address_id
├── stripe_session_id, stripe_payment_intent_id, paid_at, notes
├── payment_status: pending | paid | quotation | failed | refunded

📦 order_items (Singoli elementi dell'ordine / Lavorazioni)
├── id, order_id, product_id, quantity, unit_price, subtotal
├── customization_json (JSON con le opzioni selezionate)
├── design_file_path (Percorso file caricato)
├── work_status

📦 shipping_tiers (Scaglioni Spedizione)
├── id, name, min_order_total, cost, is_active
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
- [x] **Authenticated GraphQL**: Integrazione sicura con gateway NewWave.
- [x] **Lazy-Sync**: Controllo automatico ogni 12 ore della freschezza dati.
- [x] **Fast Availability Sync**: Aggiornamento rapido delle giacenze stock senza re-importare l'intero prodotto.
- [x] **CDN-Mode**: Sincronizzazione automatizzata delle immagini remote.

#### 🖼️ Gestione Media & Gallery
- [x] **Hybrid Gallery Engine**: Unificazione immagini locali (Spatie MediaLibrary) e remote (Images table).
- [x] **Filtro Colore Intelligente**: La gallery mostra solo le immagini associate al colore selezionato.
- [x] **Admin Gallery Control**: Interfaccia per reordering manuale e override associazioni colore per immagini API.

#### 🛒 Lavorazioni (Job-Based Cart) e Prodotti Standard
- [x] **Job UUID**: Ogni aggiunta al carrello è una lavorazione unica con UUID.
- [x] **Configuratore Prodotti Standard**: Form Livewire per prodotti ad area o quantitativi (Forex, Biglietti da visita, Banners, con calcolo `itemsPerSheet`).

---

### 🧾 Modulo 3: Pagamenti & Preventivi (COMPLETATO)
- [x] **Stripe Checkout**: Pagamento diretto con redirect a Stripe e gestione Webhooks.
- [x] **Richiesta Preventivo Privato**: Pulsante per richiedere preventivi riservati dal carrello.

---

### 🔍 Modulo 4: SEO, Feed & Social Sharing v2.0 (COMPLETATO - Luglio 2026)
- [x] **Meta Tags & Social Open Graph**: Meta tag dinamici (`og:title`, `og:description`, `og:image`, `og:url`, `twitter:*`) su prodotti, categorie e homepage.
- [x] **Gestione Anteprime Varianti in OG & Canonical**: Rilevamento automatico delle varianti esposte nella query string (es. `?colore=96`), con cambio dinamico dell'immagine Open Graph, dell'URL canonico e dei dati strutturati Schema.org.
- [x] **Google Merchant XML Feed**: Generatore automatizzato in `/feed/google-merchant.xml` per Google Shopping con dettaglio varianti (colore, taglia, immagine specifica, link con query param).
- [x] **Sitemap XML**: Mappa del sito dinamica in `/sitemap.xml` per prodotti attivi, categorie e pagine istituzionali.
- [x] **Laravel Scout v11**: Integrazione ricerca full-text per catalogo prodotti.

---

## 📍 Strategia Local SEO (Fiuggi e Dintorni)

Per dominare i risultati di ricerca locali a **Fiuggi e nei comuni limitrofi** (Anagni, Alatri, Ferentino, Sora, Frosinone, Paliano, Acuto, Piglio, Guarcino, Subiaco), senza limitarsi all'abbigliamento ma coprendo **tutti i prodotti di stampa e comunicazione visiva**, è prevista la seguente tabella di marcia SEO:

### 1. Tagging Meta & Titoli Geolocalizzati (Global & Categorie)
- **Titolo Homepage**: `Pubblicittà24 | Stampa Digitale, Grande Formato e Abbigliamento a Fiuggi`
- **Description Homepage**: `Stampa digitale professionale a Fiuggi e provincia di Frosinone: biglietti da visita, volantini, striscioni, pannelli Forex, gadget e abbigliamento personalizzato. Preventivi gratuiti online.`
- **Pagine Categoria**:
  - *Biglietti da Visita*: "Stampa Biglietti da Visita a Fiuggi e Dintorni | Pubblicittà24"
  - *Grande Formato & Pannelli*: "Stampa Grande Formato, Forex e Striscioni Fiuggi | Pubblicittà24"
  - *Volantini & Pieghevoli*: "Stampa Volantini e Pieghevoli a Fiuggi e Frosinone | Pubblicittà24"
  - *Abbigliamento da Lavoro*: "Abbigliamento da Lavoro Personalizzato Fiuggi e Ciociaria | Pubblicittà24"

### 2. Dati Strutturati Schema.org LocalBusiness / PrintShop
Integrazione in `resources/views/layouts/layout.blade.php` dello schema `LocalBusiness` / `PrintShop`:
- **Nome**: Pubblicittà24
- **Indirizzo**: Fiuggi (FR), Italia
- **Area Servita (`areaServed`)**: `["Fiuggi", "Anagni", "Alatri", "Ferentino", "Frosinone", "Sora", "Paliano", "Acuto", "Piglio", "Guarcino", "Ciociaria"]`
- **Servizi Offerti**: Stampa Digitale, Biglietti da Visita, Stampa Grande Formato, Insegne, Pannelli Rigidi, Abbigliamento Promozionale e da Lavoro.

### 3. Pagine dedicate "Servizi per Zona" (Local Landing Pages)
Creazione di pagine/sezioni target dedicate per intercettare intenti locali ad alta conversione:
- `/stampa-digitale-fiuggi`: Stampa di biglietti da visita, brochure, volantini e cataloghi per aziende ed eventi di Fiuggi e provincia.
- `/stampa-grande-formato-fiuggi`: Striscioni in PVC, banner microforati, pannelli Forex/Plexiglas, roll-up ed espositori per negozi e fiere in Ciociaria.
- `/abbigliamento-lavoro-fiuggi`: Abiti da lavoro, divise per hotel, ristoranti, centri termali e attività commerciali a Fiuggi.

### 4. Footer & Chi Siamo Geolocalizzati
- Inserimento nel footer del sito di una sezione *"Servizio di Stampa e Personalizzazione a Fiuggi e in Provincia di Frosinone"*, con elenco dei principali comuni serviti (con consegna rapida o ritiro in sede).

### 5. Integrazione Google Business Profile (Scheda Google Maps)
- Sincronizzazione del profilo Google Business di Pubblicittà24 con il sito web.
- Link diretto per recensioni clienti e mappe per rafforzare la presenza nel **Local Pack** di Google per ricerche "vicino a me".

---

## 📅 Checkpoint Aggiornati

```
LUGLIO 2026 (Stato Attuale: Completato & Local SEO Attiva)
├─ ✅ Open Graph & Meta Tags v2.0 (og:image, og:url, twitter cards)
├─ ✅ Risoluzione Varianti Esposte nei Social (Immagine specifica per ?colore=XX)
├─ ✅ Google Merchant XML Feed v1.0 (/feed/google-merchant.xml)
├─ ✅ Sitemap XML Dinamica (/sitemap.xml)
├─ ✅ Laravel Scout v11 Integration (Ricerca full-text database)
├─ ✅ Test Suite Ampliata (190+ test Pest/PHPUnit passanti)
├─ ✅ Implementazione Schema.org LocalBusiness / PrintShop (orari reali + areaServed Italia e Roma-Napoli)
├─ ✅ Meta Tag & Titoli Geolocalizzati (Fiuggi, Frosinone, Roma-Napoli e Italia)
├─ ✅ Footer Geolocalizzato con aree servite e mappa
└─ ⏳ Creazione Pagine Landing Locali dedicate (es. /stampa-grande-formato-fiuggi)
```

---

## 🧪 Test Suite

Creati oltre **180 test** (Pest/PHPUnit) passanti con successo che coprono:
- `CartTest`, `SearchTest`, `ProductPageTest`, `OrderTest`, `QuantityDiscountServiceTest`.
- Nuovi test per Open Graph, varianti esposte nei meta tag, Sitemap XML e Google Merchant Feed.
- Test formati custom e calcoli di ridimensionamento (`StandardProductResourceTest`).
- Test flusso preventivo privato e checkout con metodi di spedizione (`CheckoutTest`).

---

## 🚀 TARGET LIVE & LOCAL GROWTH: Pronto per il Rilascio e Posizionamento Locale!