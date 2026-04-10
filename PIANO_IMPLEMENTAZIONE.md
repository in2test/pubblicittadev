# 📋 Piano di Implementazione - Abbigliamento Personalizzato

**Status**: 🏗️ IN CORSO  
**Scadenza MVP**: 2 settimane  
**Ultimo aggiornamento**: 10 Aprile 2026

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
├── created_at, updated_at

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

## 🎯 Fasi Implementazione (Settimanale)

### ⚡ SETTIMANA 1: Fondamenta & Core Backend (COMPLETATA)

#### Giorni 1-2: Setup Database & Models

- [x] Creare migrazioni (products, categories, colors, sizes, variations, pricing_tiers, quotes)
- [x] Generare Models: `Product`, `Category`, `Color`, `Size`, `ProductVariation`, `PricingTier`, `Quote`, `QuoteItem`
- [x] Setup relazioni Eloquent (Focus su `ProductVariation` come pivot)
- [x] Seed database con prodotti workwear e varianti

**✅ MILESTONE 1**: Database strutturato con sistema varianti avanzato.

#### Giorni 3-5: Frontend - Catalogo & Richiesta Preventivo

- [x] Creare vista catalogo e dettaglio prodotto (`ProductController`)
- [x] Rotte dinamiche per categorie e prodotti
- [x] Logica di calcolo prezzo basata sui `pricing_tiers` nel `QuoteController`
- [x] Gestione upload file design nel form preventivo
- [x] Memorizzazione preventivo e relativi articoli nel DB

**✅ MILESTONE 2**: Flusso utente dalla scelta prodotto all'invio preventivo funzionante.

---

### 📱 SETTIMANA 2: Admin Panel & Polish (IN CORSO)

#### Giorni 8-10: Admin Panel (Filament)

- [x] Creare Filament Resource: `ProductResource` (con VariationsRelationManager)
- [x] Creare Filament Resource: `CategoryResource`
- [x] Creare Filament Resource: `ColorResource`
- [x] Creare Filament Resource: `ProductVariationResource`
- [ ] Creare Filament Resource: `QuoteResource` (Pianificato)
    - Gestione stati: pending, sent, accepted, rejected, etc.

**✅ MILESTONE 3**: Gestione catalogo completa da pannello admin.

#### Giorni 11-12: Notifiche & Automazione (DA FARE)

- [ ] Mailable: `NewQuoteNotification` (Email admin e cliente)
- [ ] Generazione PDF per i preventivi
- [ ] Integrazione notifiche nel cambio stato da Filament
- [ ] Test finale responsive e cross-browser

**✅ MILESTONE 4**: Sistema di notifiche e gestione preventivi completo.

---

## 📅 Checkpoint Aggiornati

```
SETTIMANA 1 (Recap)
├─ ✅ DB + Models (v0.1)
├─ ✅ Catalogo & Dettaglio (v0.2)
└─ ✅ Quote Form + Upload (v0.3)

SETTIMANA 2 (Attuale)
├─ ✅ Admin Prodotti (v0.6)
├─ 🏗️ Admin Preventivi (v0.7) - IN CORSO
├─ 🏗️ Email & PDF (v0.8) - DA FARE
└─ 🚀 MVP LIVE (v1.0) - TARGET: 19 Aprile
```

---

## 🛠️ Stack Tecnico

```
Backend: Laravel 13
Frontend: Blade + Tailwind 4 + Flux UI
Admin: Filament 5
Storage: local/public (quote-designs)
```

---

## ⏭️ Prossimi Passi (Immediate)

1. Implementare **Filament QuoteResource** per visualizzare i preventivi ricevuti.
2. Configurare **Mailables** per le notifiche automatiche.
3. Aggiungere logica per la generazione di **PDF** dai dettagli del preventivo.

---

## 📝 Checklist Stato Attuale

- [x] Database migrations create
- [x] Logica Varianti Prodotto (Pivot table)
- [x] Seed dati iniziali
- [x] Frontend catalogo e prodotti
- [x] Form invio preventivo con calcolo prezzi
- [x] Upload design file
- [x] Admin Prodotti/Categorie/Colori
- [ ] Admin Preventivi (QuoteResource)
- [ ] Notifiche Email
- [ ] Generazione PDF

---

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

🚀 **TARGET LIVE**: Fine Settimana 2 (19 Aprile 2026)
