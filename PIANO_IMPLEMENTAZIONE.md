# 📋 Piano di Implementazione - Abbigliamento Personalizzato

**Status**: � IN CORSO  
**Scadenza MVP**: 2 settimane  
**Ultimo aggiornamento**: 7 Aprile 2026  

---

## 📊 Panoramica Progetto

**Nome**: Plataforma di E-commerce per stampe personalizzate su abbigliamento  
**MVP Focus**: Abbigliamento (Workwear - Basic Hoody, Basic Roundneck, etc.)  
**Flusso**: Quote-based (No pagamento online) → Ordini manuali → Admin gestisce  
**Lingua**: Italiano  
**Team**: 1 developer  
**Timeline**: 2 settimane  

---

## 🗄️ Struttura Database Necessaria

### Tabelle Principali

```
📦 products
├── id, name, slug, description, base_price
├── material (100% cotone, poliestere, etc.)
├── category (T-shirt, Hoodie, Polo, etc.)
├── sku_prefix
├── is_active
├── created_at, updated_at

📦 product_colors
├── id, product_id, color_name, color_hex, sort_order
├── unique: (product_id, color_name)

📦 customization_points
├── id, name (es. "Fronte - Full", "Fronte - Petto", "Retro - Full", "Maniche", "Gamba Pantaloni", "Tasca")
├── category (es. "fronte", "retro", "maniche", "pantaloni")
├── description
├── display_order

📦 pricing_tiers
├── id, product_id, min_quantity, max_quantity, price_per_unit
├── es: (1-5: €15, 6-10: €12, 11+: €10)

📦 quotes (Ordini)
├── id, quote_number, customer_name, customer_email, customer_phone, whatsapp
├── total_items, total_price, notes
├── status (pending, quote_sent, accepted, rejected, production, completed)
├── created_at, updated_at

📦 quote_items (Articoli per ordine)
├── id, quote_id, product_id, color_id, quantity, subtotal
├── customization_json (JSON con le opzioni selezionate)
├── design_file_path (PDF/image caricato dal cliente)

📦 quote_item_customizations (Dettagli per articolo)
├── id, quote_item_id, customization_point_id, selected (true/false)
```

---

## 🎯 Fasi Implementazione (Settimanale)

### ⚡ SETTIMANA 1: Fondamenta & MVP Minimo

#### Giorni 1-2: Setup Database & Models
- [x] Creare migrazioni per tutte le tabelle (products, colors, customization_points, pricing_tiers, quotes, quote_items)
- [x] Generare Models: `Product`, `ProductColor`, `CustomizationPoint`, `PricingTier`, `Quote`, `QuoteItem`
- [x] Setup relazioni Eloquent
- [ ] Seed database con:
  - 5-6 prodotti workwear (Basic Hoody, Basic Roundneck, etc.)
  - 20 colori
  - Punti personalizzazione standard
  - Tier prezzi di default

**✅ MILESTONE 1 - Pronto per deploy**: Database strutturato, modelli funzionali

#### Giorni 3-4: Frontend - Pagina Catalogo & Selezione Prodotto
- [ ] Creare vista pubblica: `/abbigliamento` (lista prodotti)
- [ ] Componente Livewire: `SelectProduct`
  - Seleziona prodotto → mostra colori disponibili
  - Seleziona colore → visualizza preview (immagine colore placeholder)
  - Mostra punti personalizzazione selezionabili (checkbox)
- [ ] Routing pubblico: `web.php` (nessun auth)
- [ ] Styling Tailwind base per catalogo

**✅ MILESTONE 2 - Pronto per deploy**: Catalogo visibile, selezione prodotto funzionante

#### Giorni 5: Form Quote & Upload Design
- [x] Componente Livewire / controller: `QuoteForm` / `QuoteController`
  - Quantità (numero) → calcolo prezzo tramite tier di prezzo
  - Caricamento file design (PDF/JPG/PNG)
  - Form cliente: nome, email, telefono, WhatsApp
  - Area note/special requests
  - Validazione input
- [ ] Store file in `storage/app/quote-designs` (privato)
- [x] Aggiungere route POST `/quote`

**✅ MILESTONE 3 - In corso**: Quote form integrato e request handler creato

#### Giorni 6-7: Email & Admin Notifiche
- [ ] Mailable: `NewQuoteNotification` 
  - Email admin con dettagli ordine
  - Email cliente con conferma ricevimento + numero quote
- [ ] Aggiungere quote_number generato automaticamente (formato: `QT-20260407-001`)
- [ ] Setup email `.env` per testing
- [ ] Test manuale flusso completo

**✅ MILESTONE 4 - PRONTO WEEK 1**: MVP base online, quote funzionante end-to-end

---

### 📱 SETTIMANA 2: Admin Panel & Polish

#### Giorni 8-9: Admin Panel - Vista Quote (Filament)
- [ ] Creare Filament Resource: `QuoteResource`
  - Tabella quote con filtri (status, date, customer)
  - Dettagli quote con:
    - Info cliente
    - Lista articoli con customizzazioni
    - Link download design file
    - Campo note interno admin
- [ ] Actions:
  - Visualizza completo
  - Cambia stato (pending → quote_sent → production → completed)
  - Invia email conferma al cliente
  - Scarica ordine PDF

**✅ MILESTONE 5**: Admin può visualizzare e gestire quote

#### Giorni 10: Admin Panel - Gestione Prodotti (Filament)
- [ ] Creare Filament Resource: `ProductResource`
  - CRUD prodotti
  - Gestisci colori per prodotto
  - Gestisci tier prezzi
- [ ] Creare Filament Resource: `CustomizationPointResource`
  - CRUD punti personalizzazione (per future fasi)

**✅ MILESTONE 6**: Admin può gestire inventario prodotti

#### Giorni 11-12: Testing, Optimizzazione & Deploy
- [ ] Test completo workflow:
  - Cliente visita catalogo → seleziona prodotto+colore+custom → carica design → riceve email
  - Admin accede panel → vede quote → scarica file → cambia stato → manda email
- [ ] Cross-browser testing (desktop + mobile)
- [ ] Performance check (load time, query optimization)
- [ ] Testi UI in italiano completo
- [ ] Setup `.env` production
- [ ] **DEPLOY MVP LIVE** 🚀

**✅ MILESTONE 7 - MVP LIVE**: Sito online, quote funzionante, admin attivo

---

## 📅 Checkpoint Settimanali

```
SETTIMANA 1
├─ Giorno 2: ✅ DB + Models (DEPLOY v0.1)
├─ Giorno 4: ✅ Catalogo (DEPLOY v0.2)
├─ Giorno 5: ✅ Quote Form (DEPLOY v0.3)
└─ Giorno 7: ✅ Email Funzionante (DEPLOY v0.4-MVP)

SETTIMANA 2
├─ Giorno 9: ✅ Admin Quotes (DEPLOY v0.5)
├─ Giorno 10: ✅ Admin Products (DEPLOY v0.6)
└─ Giorno 12: ✅ MVP LIVE (DEPLOY v1.0) 🚀
```

---

## 🛠️ Stack Tecnico Utilizzato

```
Backend: Laravel 13 + Livewire 4
Admin: Filament 5
Frontend: Tailwind 4 + Flux UI 2
Database: [Current DB configured]
File Upload: Storage API (public/quote-designs)
Email: Laravel Mail (SMTP configured)
Testing: Pest 4
Formatting: Pint
```

---

## ⏭️ Phase 2 (Dopo MVP Live)

Once MVP è stabile e generando quote:

1. **Portfolio Portfolio Grafica** (1-2 giorni)
   - Creare sezione portfolio con galleria progetti passati
   - Link da form custom design

2. **Business Cards** (3-4 giorni)
   - Aggiungere categoria business cards
   - Personalizzazione: fronte/retro, tipo laminazione
   - Calcolo prezzo per esigenze

3. **Stampe Grandi** (3-4 giorni)
   - Aggiungere prodotti large format
   - Sistema personalizzazione custom dimensioni

4. **Sistema Pagamento Online** (2-3 giorni)
   - Aggiungere Stripe/PayPal para prodotti standard
   - Mantenere quote per custom

5. **Tracking Ordini** (1-2 giorni)
   - Link tracking per cliente
   - Notifiche via email/SMS

---

## 📝 Checklist Implementazione

### Pre-Development
- [ ] Accesso server/deploy configurato
- [ ] Email SMTP configurato (test@example.com)
- [ ] Storage directory permissions OK
- [ ] `.env` dev configurato
- [ ] Database locale avviato e accessibile (MySQL attualmente non raggiungibile)

### Development Week 1
- [ ] Database migrations create ✓
- [ ] Seed data prodotti/colori ✓
- [ ] Frontend catalogo ✓
- [ ] Componente selezione prodotto ✓
- [ ] Form quote + upload ✓
- [ ] Email notifiche ✓
- [ ] Testing workflow completo ✓

### Development Week 2
- [ ] Admin panel Filament - Quote Resource ✓
- [ ] Admin panel - Product Resource ✓
- [ ] PDF generate ordini ✓
- [ ] UI/UX polish ✓
- [ ] Mobile responsive check ✓
- [ ] Testi italiani completi ✓
- [ ] Production deploy ✓

### Post-Launch
- [ ] Monitoraggio errori (Sentry/Logs)
- [ ] User feedback collection
- [ ] Performance monitoring
- [ ] Plan Phase 2

---

## 🚨 Rischi & Mitigation

| Rischio | Mitigation |
|---------|-----------|
| Upload file non funzionante | Test su staging con file grandi |
| Email spam folder | Configurare SPF/DKIM correttamente |
| Performance lenta DB | Aggiungere indici su quote.customer_email, quotes.created_at |
| Mobile UI rotta | Testare su device reali, media-query Tailwind |
| Quote form validation edge cases | Test dataset comprensivo prima deploy |

---

## 📞 Comunicazione Cliente

**Email automatiche (Template italiano)**:
1. Quote ricevuta: "Grazie per il vostro interesse..."
2. Quote pronta: "La vostra quotazione è pronta..."
3. Ordine confermato: "Proceediamo con la produzione..."
4. Ordine spedito: "Il vostro ordine è in viaggio..."

---

**Note Finali**:
- Deploy incremental ogni milestone = riduce rischio breaking changes
- Ogni giorno check → se blocco, risolvere subito
- Week 2 = 50% new features + 50% testing e stabilità
- Post-launch: priorità = bug fixing e feature feedback

🚀 **TARGET LIVE**: Fine Settimana 2 (19 Aprile 2026)
