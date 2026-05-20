<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Image;
use App\Models\PricingTier;
use App\Models\PrintSide;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandardProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure categories exist
        $this->call(CategorySeeder::class);
        $this->call(PrintSideSeeder::class);

        $piccolo = Category::where('slug', 'piccolo_formato')->firstOrFail();
        $grande = Category::where('slug', 'grande_formato')->firstOrFail();
        $espositori = Category::where('slug', 'espositori')->firstOrFail();
        $gadget = Category::where('slug', 'gadget_promozionale')->firstOrFail();

        // 2. Product definitions
        // Each entry: name, slug, sku, description, price, pricing_model, min_area,
        //             max_width (cm), max_height (cm), category_id, is_featured,
        //             image_url, variations, print_sides, pricing_tiers
        //
        // variations = [ 'Type Name' => [ 'Option Name' => 'value', ... ], ... ]
        // pricing_tiers = [ [ min_qty, max_qty|null, price, print_side_name|null ], ... ]

        $products = [

            // ─────────────────────────────────────────────
            //  PICCOLO FORMATO
            // ─────────────────────────────────────────────

            [
                'name' => 'Biglietti da Visita',
                'slug' => 'biglietti-da-visita',
                'sku' => 'STD-BDV',
                'description' => 'Biglietti da visita stampati su cartoncino premium. Disponibili in 350g e 400g con varie finiture di plastificazione. Angoli vivi o arrotondati, formato standard 8,5×5,5 cm. Stampa offset ad alta definizione per colori brillanti e testo nitido. Indispensabili per presentare la tua attività con stile.',
                'price' => 0.08,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $piccolo->id,
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1589149013831-c40ab39478f7?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Grammatura' => [
                        '350g Patinata' => '350g',
                        '400g Patinata' => '400g',
                    ],
                    'Finitura' => [
                        'Nessuna finitura' => 'nessuna',
                        'Plastificazione Opaca' => 'opaca',
                        'Plastificazione Lucida' => 'lucida',
                        'Soft Touch' => 'soft-touch',
                        'Effetto Lino' => 'lino',
                    ],
                    'Angoli' => [
                        'Angoli vivi' => 'vivi',
                        'Angoli arrotondati' => 'arrotondati',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali', 'Fronte e retro differenti'],
                'pricing_tiers' => [
                    [50,   99,   0.15, null],
                    [100,  249,  0.10, null],
                    [250,  499,  0.07, null],
                    [500,  999,  0.05, null],
                    [1000, null, 0.04, null],
                ],
            ],

            [
                'name' => 'Volantini e Flyer',
                'slug' => 'volantini-flyer',
                'sku' => 'STD-FLY',
                'description' => 'Volantini e flyer promozionali su carta patinata o riciclata. Disponibili nei formati A6, A5 e A4 con grammature da 115g a 200g. Ideali per la distribuzione sul territorio, eventi e campagne pubblicitarie. Stampa a 4 colori fronte/retro con resa cromatica eccellente.',
                'price' => 0.05,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $piccolo->id,
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1596638787647-904d822d751e?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Formato' => [
                        'A6 (10,5×14,8 cm)' => 'A6',
                        'A5 (14,8×21 cm)' => 'A5',
                        'A4 (21×29,7 cm)' => 'A4',
                    ],
                    'Grammatura' => [
                        '115g Patinata Lucida' => '115g-lucida',
                        '135g Patinata Lucida' => '135g-lucida',
                        '135g Patinata Opaca' => '135g-opaca',
                        '170g Patinata Lucida' => '170g-lucida',
                        '170g Patinata Opaca' => '170g-opaca',
                        '200g Patinata Lucida' => '200g-lucida',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali'],
                'pricing_tiers' => [
                    [100,  249,  0.09, null],
                    [250,  499,  0.06, null],
                    [500,  999,  0.05, null],
                    [1000, 2499, 0.04, null],
                    [2500, null, 0.03, null],
                ],
            ],

            [
                'name' => 'Pieghevoli',
                'slug' => 'pieghevoli',
                'sku' => 'STD-PIE',
                'description' => 'Pieghevoli personalizzati in vari formati di piega: bifold, trifold (a fisarmonica o a portafoglio) e gate-fold. Stampati su carta patinata da 135g o 170g con finiture opaca o lucida. Perfetti per brochure aziendali, menu da ristorante, programmi di eventi e presentazioni di prodotti.',
                'price' => 0.12,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $piccolo->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1568667256549-094345857672?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Tipo di piega' => [
                        'Bifold (A4 → A5)' => 'bifold-a4',
                        'Trifold (A4 → DL)' => 'trifold-dl',
                        'Trifold A4 a fisarmonica' => 'fisarmonica-a4',
                        'Gate fold (A4)' => 'gatefold-a4',
                    ],
                    'Grammatura' => [
                        '135g Patinata Lucida' => '135g-lucida',
                        '135g Patinata Opaca' => '135g-opaca',
                        '170g Patinata Lucida' => '170g-lucida',
                        '170g Patinata Opaca' => '170g-opaca',
                    ],
                ],
                'print_sides' => ['Fronte e retro uguali', 'Fronte e retro differenti'],
                'pricing_tiers' => [
                    [100,  249,  0.18, null],
                    [250,  499,  0.13, null],
                    [500,  999,  0.10, null],
                    [1000, null, 0.08, null],
                ],
            ],

            [
                'name' => 'Locandine e Poster',
                'slug' => 'locandine-poster',
                'sku' => 'STD-LOC',
                'description' => 'Locandine e poster su carta patinata lucida o opaca ad alta grammatura. Formati da A3 fino al 70×100 cm. Stampa ad altissima definizione per immagini fotografiche. Ideali per la comunicazione indoor di eventi, promozioni, concerti e mostre. Resa cromatica premium con colori saturi e nitidi.',
                'price' => 0.35,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $piccolo->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Formato' => [
                        'A3 (29,7×42 cm)' => 'A3',
                        'A2 (42×59,4 cm)' => 'A2',
                        'A1 (59,4×84,1 cm)' => 'A1',
                        'A0 (84,1×118,9 cm)' => 'A0',
                        '50×70 cm' => '50x70',
                        '70×100 cm' => '70x100',
                    ],
                    'Grammatura' => [
                        '135g Patinata Lucida' => '135g-lucida',
                        '200g Patinata Lucida' => '200g-lucida',
                        '250g Patinata Lucida' => '250g-lucida',
                        '300g Patinata Opaca' => '300g-opaca',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [
                    [10,  24,  0.70, null],
                    [25,  49,  0.50, null],
                    [50,  99,  0.38, null],
                    [100, null, 0.30, null],
                ],
            ],

            // ─────────────────────────────────────────────
            //  GRANDE FORMATO – BANDIERE / BANNER
            // ─────────────────────────────────────────────

            [
                'name' => 'Striscioni Banner PVC',
                'slug' => 'striscioni-banner-pvc',
                'sku' => 'STD-BAN',
                'description' => 'Striscioni pubblicitari in PVC calandrato da 510g/mq con stampa UV a colori diretti. Resistenti all\'acqua, ai raggi UV e al vento. Bordi rinforzati con orlo saldato ed occhielli in acciaio inox ogni 50 cm. Versione microforato disponibile per installazioni ventose. Ideali per cantieri, eventi, esposizioni e segnaletica esterna.',
                'price' => 13.50,
                'pricing_model' => 'area',
                'min_area' => 1.0,
                'max_width' => 300.0,
                'max_height' => null,
                'category_id' => $grande->id,
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1562259929-b4e1fd30ec50?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Materiale' => [
                        'PVC Standard 510g' => 'pvc-510',
                        'PVC Rinforzato 680g' => 'pvc-680',
                        'Microforato Mesh (wind-proof)' => 'mesh',
                    ],
                    'Occhielli' => [
                        'Senza occhielli' => 'no-occhielli',
                        'Con occhielli ogni 50 cm' => 'occhielli-50',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [],
            ],

            [
                'name' => 'Pannelli Forex Rigido',
                'slug' => 'pannelli-in-forex',
                'sku' => 'STD-FOREX',
                'description' => 'Stampa UV diretta su pannelli rigidi in Forex (PVC espanso). Materiale leggero, pianare e resistente all\'umidità. Ideale per cartellonistica indoor/outdoor, allestimenti fieristici, mostre fotografiche e insegne. Disponibile in spessori da 3 a 10 mm. Area minima fatturabile 0,5 mq per pezzo.',
                'price' => 18.00,
                'pricing_model' => 'area',
                'min_area' => 0.5,
                'max_width' => 200.0,
                'max_height' => 300.0,
                'category_id' => $grande->id,
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Spessore Forex' => [
                        'Forex 3 mm' => '3mm',
                        'Forex 5 mm' => '5mm',
                        'Forex 10 mm' => '10mm',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali'],
                'pricing_tiers' => [],
            ],

            [
                'name' => 'Pannelli in Dibond (Alluminio)',
                'slug' => 'pannelli-dibond-alluminio',
                'sku' => 'STD-DIB',
                'description' => 'Pannelli compositi in Dibond (alluminio 3mm) con stampa UV diretta ad alta definizione. Materiale estremamente rigido, leggero e resistente alle intemperie. Perfetti per insegne esterne, targa d\'azienda, comunicazione architettonica e allestimenti di lunga durata. Superficie con finitura silver o bianca.',
                'price' => 28.00,
                'pricing_model' => 'area',
                'min_area' => 0.5,
                'max_width' => 150.0,
                'max_height' => 300.0,
                'category_id' => $grande->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Finitura Pannello' => [
                        'Dibond Silver (alluminio)' => 'silver',
                        'Dibond Bianco' => 'bianco',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [],
            ],

            [
                'name' => 'Adesivi in PVC',
                'slug' => 'adesivi-pvc',
                'sku' => 'STD-ADH',
                'description' => 'Pellicola adesiva in PVC monomerico o poliestere per applicazioni su superfici piane. Disponibile in variante bianco lucida, bianca opaca e trasparente. Resistente ai raggi UV e alle intemperie per applicazioni esterne fino a 3 anni. Ideale per vetrine, pareti, veicoli e segnaletica.',
                'price' => 11.00,
                'pricing_model' => 'area',
                'min_area' => 0.2,
                'max_width' => 150.0,
                'max_height' => null,
                'category_id' => $grande->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1572375995301-4018d3aea593?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Finitura' => [
                        'Bianco Lucido' => 'bianco-lucido',
                        'Bianco Opaco' => 'bianco-opaco',
                        'Trasparente Lucido' => 'trasparente',
                    ],
                    'Taglio' => [
                        'Rettangolare (senza fustella)' => 'rettangolare',
                        'Sagomato su file di taglio' => 'sagomato',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [],
            ],

            [
                'name' => 'Stampa su Tela Canvas',
                'slug' => 'stampa-tela-canvas',
                'sku' => 'STD-CAN',
                'description' => 'Stampa fotografica su tela canvas in poliestere, montata su telaio in legno di abete da 2 cm. Tinture a getto d\'inchiostro UV con colori vividi e dettagli perfetti. Ideale per riproduzioni artistiche, foto di famiglia, decorazione d\'interni e realizzazione di opere personalizzate.',
                'price' => 22.00,
                'pricing_model' => 'area',
                'min_area' => 0.04,
                'max_width' => 100.0,
                'max_height' => 150.0,
                'category_id' => $grande->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1578301978018-3005759f48f7?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Profondità telaio' => [
                        'Telaio 2 cm standard' => '2cm',
                        'Telaio 4 cm profondo' => '4cm',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [],
            ],

            // ─────────────────────────────────────────────
            //  ESPOSITORI
            // ─────────────────────────────────────────────

            [
                'name' => 'Roll-Up Espositore',
                'slug' => 'roll-up-espositore',
                'sku' => 'STD-RUP',
                'description' => 'Espositore roll-up avvolgibile con struttura in alluminio anodizzato e telo stampato in alta definizione su PET da 190g. Disponibile nei formati 85×200 cm e 100×200 cm. Include sacca da trasporto. Perfetto per fiere, convegni, showroom e punti vendita. Montaggio in pochi secondi senza attrezzi.',
                'price' => 49.00,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $espositori->id,
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Formato Roll-Up' => [
                        '80×200 cm' => '80x200',
                        '100×200 cm' => '100x200',
                        '120×200 cm' => '120x200',
                    ],
                    'Qualità struttura' => [
                        'Eco (struttura base)' => 'eco',
                        'Premium (struttura spessa)' => 'premium',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [
                    [2,  4,  45.00, null],
                    [5,  9,  42.00, null],
                    [10, null, 39.00, null],
                ],
            ],

            [
                'name' => 'Display da Tavolo L-Shape',
                'slug' => 'display-tavolo-lshape',
                'sku' => 'STD-DSP',
                'description' => 'Porta locandine da tavolo in PVC rigido trasparente a forma di L. Disponibile nei formati A6, A5 e A4. Incluso insert in carta stampata fronte o fronte/retro. Ideale per ristoranti, hotel, reception aziendali, farmacie e negozi. Allestimento rapido, aspetto professionale.',
                'price' => 3.50,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $espositori->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1497032628192-86f99bcd76bc?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Formato display' => [
                        'A6 orizzontale' => 'a6-h',
                        'A5 verticale' => 'a5-v',
                        'A4 verticale' => 'a4-v',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro differenti'],
                'pricing_tiers' => [
                    [10, 24,  3.00, null],
                    [25, 49,  2.60, null],
                    [50, null, 2.20, null],
                ],
            ],

            [
                'name' => 'Totem Espositore in Forex',
                'slug' => 'totem-espositore-forex',
                'sku' => 'STD-TOT',
                'description' => 'Totem pubblicitario autoportante realizzato in Forex 10 mm con stampa UV diretta. Base di appoggio inclusa in kit. Altezze disponibili da 100 a 200 cm. Ideale per eventi, showroom, centri commerciali e campagne promozionali. Leggero, personalizzabile e di grande impatto visivo.',
                'price' => 25.00,
                'pricing_model' => 'area',
                'min_area' => 0.5,
                'max_width' => 100.0,
                'max_height' => 200.0,
                'category_id' => $espositori->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Forma totem' => [
                        'Rettangolare standard' => 'rettangolare',
                        'Sagomato personalizzato' => 'sagomato',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali'],
                'pricing_tiers' => [],
            ],

            // ─────────────────────────────────────────────
            //  GADGET & PROMOZIONALE
            // ─────────────────────────────────────────────

            [
                'name' => 'Sacchetti Shopper Carta Kraft',
                'slug' => 'sacchetti-shopper-carta-kraft',
                'sku' => 'STD-SAC',
                'description' => 'Sacchetti shopper in carta Kraft naturale con manici in cotone twill. Disponibili in vari formati con grammatura da 90g a 120g. Stampa serigrafica o digitale in 1-4 colori su uno o entrambi i lati. Ideali per negozi, boutique, mercati e confezionamento regalo. Soluzione eco-friendly e personalizzabile.',
                'price' => 1.20,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $gadget->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1605027990121-cbae9e0642df?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Formato sacchetto' => [
                        'Piccolo (18×22×8 cm)' => 'piccolo',
                        'Medio (32×42×12 cm)' => 'medio',
                        'Grande (40×50×14 cm)' => 'grande',
                    ],
                    'Colore Kraft' => [
                        'Naturale (avana)' => 'naturale',
                        'Bianco' => 'bianco',
                        'Nero' => 'nero',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali'],
                'pricing_tiers' => [
                    [50,  99,   1.10, null],
                    [100, 249,  0.95, null],
                    [250, 499,  0.80, null],
                    [500, null, 0.65, null],
                ],
            ],

            [
                'name' => 'Braccialetti in Tyvek',
                'slug' => 'braccialetti-tyvek',
                'sku' => 'STD-BRA',
                'description' => 'Braccialetti in Tyvek monouso per il controllo degli accessi a eventi, concerti, festival, parchi e fiere. Resistenti all\'acqua e alla lacerazione. Stampa a 4 colori su fondo bianco. Chiusura con clip adesiva antimanomissione. Numero sequenziale disponibile su richiesta.',
                'price' => 0.18,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $gadget->id,
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Colore braccialetto' => [
                        'Bianco' => 'bianco',
                        'Rosso' => 'rosso',
                        'Verde' => 'verde',
                        'Blu' => 'blu',
                        'Giallo' => 'giallo',
                        'Nero' => 'nero',
                        'Arancione' => 'arancione',
                        'Rosa' => 'rosa',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [
                    [100,  249,  0.16, null],
                    [250,  499,  0.13, null],
                    [500,  999,  0.10, null],
                    [1000, null, 0.07, null],
                ],
            ],

            [
                'name' => 'Tazze in Ceramica Personalizzate',
                'slug' => 'tazze-ceramica-personalizzate',
                'sku' => 'STD-TAZ',
                'description' => 'Tazze in ceramica bianca da 300ml con stampa sublimatica ad alta definizione. Resistenti alla lavastoviglie. Il colore viene direttamente integrato nella ceramica, risultando permanente e brillante. Ideali come gadget aziendali, regali personalizzati e premi per eventi.',
                'price' => 8.50,
                'pricing_model' => 'unit',
                'min_area' => null,
                'max_width' => null,
                'max_height' => null,
                'category_id' => $gadget->id,
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Colore tazza' => [
                        'Bianca classica' => 'bianca',
                        'Bianca interno colorato Rosso' => 'interno-rosso',
                        'Bianca interno colorato Blu' => 'interno-blu',
                        'Bianca interno colorato Nero' => 'interno-nero',
                        'Color-Changing (magica)' => 'magic',
                    ],
                    'Formato' => [
                        '300 ml standard' => '300ml',
                        '450 ml maxi' => '450ml',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
                'pricing_tiers' => [
                    [10,  24,  7.50, null],
                    [25,  49,  6.80, null],
                    [50,  99,  6.00, null],
                    [100, null, 5.20, null],
                ],
            ],
        ];

        // 3. Seed each product
        foreach ($products as $pData) {
            $this->command->info("Seeding product: {$pData['name']}");

            // Create / update product
            $product = Product::updateOrCreate(
                ['slug' => $pData['slug']],
                [
                    'name' => $pData['name'],
                    'sku' => $pData['sku'],
                    'description' => $pData['description'],
                    'price' => $pData['price'],
                    'pricing_model' => $pData['pricing_model'],
                    'min_area' => $pData['min_area'],
                    'max_width' => $pData['max_width'] ?? null,
                    'max_height' => $pData['max_height'] ?? null,
                    'category_id' => $pData['category_id'],
                    'type' => Product::TYPE_STANDARD,
                    'is_active' => true,
                    'is_featured' => $pData['is_featured'],
                ]
            );

            // Image
            Image::updateOrCreate(
                ['product_id' => $product->id, 'image_url' => $pData['image_url']],
                ['image_description' => $pData['name'], 'order_by' => 0]
            );

            // Variation types, options & product_variation_types
            $variationOptionsMap = []; // variation_type_id => [VariationOption, ...]
            $sortIdx = 0;
            foreach ($pData['variations'] as $vTypeName => $optionsData) {
                $varType = VariationType::firstOrCreate(
                    ['name' => $vTypeName],
                    ['presentation_type' => 'select']
                );

                $productVarType = ProductVariationType::firstOrCreate(
                    ['product_id' => $product->id, 'variation_type_id' => $varType->id],
                    ['has_images' => false, 'affects_price' => false, 'sort_order' => $sortIdx]
                );
                $sortIdx++;

                $variationOptionsMap[$varType->id] = [];
                $optSortIdx = 0;

                foreach ($optionsData as $optName => $optValue) {
                    $option = VariationOption::firstOrCreate(
                        ['variation_type_id' => $varType->id, 'value' => $optValue],
                        ['name' => $optName, 'sort_order' => $optSortIdx]
                    );

                    ProductVariationOption::firstOrCreate([
                        'product_variation_type_id' => $productVarType->id,
                        'variation_option_id' => $option->id,
                    ]);

                    $variationOptionsMap[$varType->id][] = $option;
                    $optSortIdx++;
                }
            }

            // Delete existing SKUs for a clean reseed
            $product->skus()->each(function (ProductSku $sku): void {
                DB::table('product_sku_options')->where('product_sku_id', $sku->id)->delete();
                $sku->delete();
            });

            // Build Cartesian product of options → one SKU per combination
            $combinations = $this->getCombinations($variationOptionsMap);
            $skuIndex = 1;

            foreach ($combinations as $combo) {
                $productSku = ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => $pData['sku'].'-'.str_pad((string) $skuIndex++, 3, '0', STR_PAD_LEFT),
                    'quantity' => 9999,
                    'is_available' => true,
                ]);

                foreach ($combo as $option) {
                    DB::table('product_sku_options')->insert([
                        'product_sku_id' => $productSku->id,
                        'variation_option_id' => $option->id,
                    ]);
                }
            }

            // Print sides
            $product->printSides()->detach();
            foreach ($pData['print_sides'] as $sideName) {
                $side = PrintSide::where('name', $sideName)->first();
                if ($side) {
                    $product->printSides()->attach($side->id);
                }
            }

            // Pricing tiers
            PricingTier::where('product_id', $product->id)->delete();
            foreach ($pData['pricing_tiers'] as [$minQty, $maxQty, $price, $sideName]) {
                $sideId = null;
                if ($sideName) {
                    $sideId = PrintSide::where('name', $sideName)->value('id');
                }

                PricingTier::create([
                    'product_id' => $product->id,
                    'min_quantity' => $minQty,
                    'max_quantity' => $maxQty,
                    'price_per_unit' => $price,
                    'print_side_id' => $sideId,
                ]);
            }
        }

        $this->command->info('Standard products seeded successfully!');
    }

    /**
     * Generate Cartesian product of variation options.
     *
     * @param  array<int, list<VariationOption>>  $arrays
     * @return list<array<int, VariationOption>>
     */
    private function getCombinations(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $values) {
            $tmp = [];
            foreach ($result as $resultItem) {
                foreach ($values as $value) {
                    $tmp[] = array_merge($resultItem, [$value]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }
}
