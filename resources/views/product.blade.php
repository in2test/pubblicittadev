<x-layout>

    <!-- Breadcrumbs -->
    <nav
        class="px-8 3xl:px-32 py-4 flex items-center gap-2 mb-12 text-xs font-mono uppercase tracking-widest text-secondary">
        <a class="hover:text-primary transition-colors" href="#">Home</a>
        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
        @if($category->parent)
            <a class="hover:text-primary transition-colors"
                href="{{ route('category', $category->parent->slug) }}">{{ $category->parent->name }}</a>
            <span class="material-symbols-outlined text-[10px]">chevron_right</span>
        @endif
        @if($category)
            <a class="hover:text-primary transition-colors"
                href="{{ route('category', $category->slug) }}">{{ $category->name }}</a>
            <span class="material-symbols-outlined text-[10px]">chevron_right</span>
        @endif
        <span class="text-on-surface font-bold">{{ $product->name }}</span>
    </nav>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Left Column: Gallery -->
        <div class="lg:col-span-7 space-y-4">
            <div class="aspect-[4/5] bg-surface-container-lowest border border-outline-variant/10 overflow-hidden">
                <img alt="Guscio Tecnico Pro-X Front View" class="w-full h-full object-cover"
                    data-alt="Technical waterproof jacket front display on white"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCyjIBjsVo9KXptLYQaeXsgShJSqmUGpwCgOR1WFrv_b3jIPJtHkBpTP5l_G2YalcFSPOeIzWgZ-xyEwsRrAgCrxQXnig_dhj85xDKjMjuzq0fk6aEdML_vpwKgYgZT449IsEkZ30EZVMvpWN4bBD_yKpDHJl4gp2-aGZiC0PzhBljOJl44KtVVFNiEgR1U7uls05_u44sV60mNpeLDKoFnB8e_5-atT4na5IChUK8lT89VzVFmBIa6MVpy79aaMf8H7XPurkbp6r0" />
            </div>
            <div class="grid grid-cols-4 gap-4">
                <div class="aspect-square bg-surface-container border-2 border-primary overflow-hidden">
                    <img alt="Detail 1"
                        class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                        data-alt="Technical jacket shoulder detail shot"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuCZmfG2xWocpKZvY70n0MR_9msd9cGJinDLWXrBri8HWFNCBhtWAInGV9joVljgNaGraiN9CmGm3CQ9xNTMnBOyS_DVsqXxAv8rRoIU41NH4IwH1hpl8GId6plkdeqp_4RWOuHoJ4ndJiOsQKJz3XDK-bpbWeBo9tYzOWpZscc_QOEM38LvMhrH85q5Gf5W1qRhrneq0ZFOHJ14IxMR6Qww9jGZKAPAAITXLMN_KPY5WEiPFIHxhV4PkhK0dJS2pjgkB-U8FaLS3Fs" />
                </div>
                <div class="aspect-square bg-surface-container overflow-hidden">
                    <img alt="Detail 2"
                        class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                        data-alt="Waterproof membrane close up macro shot"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBMIom4u_99mUNO5AdJ17_K7kcn0Mu2XcOJl18aAcGf_zNS4nUmEcbhZSSFpkgWlihxgdLAUDYrqhYVbbWg9rbwU1X2EfzE1TuROmsPNWnCyBBk2aOT-yPTMjuqTgFcfTQ5BSGNHuGr_iDbWENGCeSBVJhzCUKLnBEDUw6LLe9XU-HlgQsxb1Amt8fQxRme8d5tw7xd4wlvb08SJgaOTLw8G8L-7suKczdbugOET7a1n1-WvaGHT-_k90mWZ6wa4c8GjUSQY6gOrAA" />
                </div>
                <div class="aspect-square bg-surface-container overflow-hidden">
                    <img alt="Detail 3"
                        class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                        data-alt="Jacket internal pocket and lining view"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDA4Ff94G1yY8oNag6NIqd7ABPVbs7zBZc3NLkJHe53GkR2q1bt7ZUw2uQXzLWjZfd-ZI_licmQF6hdudF3rWtfiS8DWI8w8l4j_nqpzK-qF7tIfZ6O9HwLRDbjbhMfzGWuQa-uzexshySuVJYJTRFaWl85Cek_PZXJnSlGxTqmhXD24EIOJf8XIP7EcglNo5ov4IwFzP_uRrmG20dxyrBGloVanVwA47LSs4TesrIamIr4_B0CEt6HzQ5SmEykPFiHllymofvAeew" />
                </div>
                <div class="aspect-square bg-surface-container overflow-hidden">
                    <img alt="Detail 4"
                        class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                        data-alt="Jacket back view highlighting reflective strips"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuADFbJa2xGUYhU1bknO-QoqY5ghBfIyenZop-dLhJue-8s6EclK_vtPsoxZOJs7qPKmRl8ngesrkMiIxHGUHQ5NYeVZABKE4GyCUWvvsTk4I67K7fmRWZTwKLJbOlsJuNKWvXnlbyHJDFIQk2e9zR5Kw3F802eZgJEdqHMWUtZ7nv8jtdpay33RfBPoMjuT4ioM3X1PICOC44mtEa3P2tIFS0k41KWWGBzR21G2lVRPSGSHxIYifG05s2_v3vaGtT7aQA137TQIgBU" />
                </div>
            </div>
        </div>
        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 flex flex-col">
            <div class="mb-2">
                <span class="font-mono text-[10px] tracking-tighter text-secondary bg-surface-container px-2 py-1">SKU:
                    OP-PRX-2024-BLK</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-on-surface mb-4 leading-none uppercase">
                Guscio Tecnico<br />Pro-X
            </h1>
            <div class="flex items-baseline gap-4 mb-8">
                <span class="text-3xl font-light text-primary tracking-tight">€189,00</span>
                <span class="text-xs font-mono text-secondary">IVA ESCLUSA</span>
            </div>
            <div class="mb-8 p-6 bg-surface-container-low border-l-4 border-primary">
                <p class="text-sm text-on-surface-variant leading-relaxed">
                    Progettato per condizioni estreme, il Guscio Pro-X combina una membrana a tre strati ad alta
                    traspirabilità con una resistenza all'abrasione industriale. Ideale per operatori tecnici in
                    ambienti esterni e logistica specializzata.
                </p>
            </div>
            <!-- Configuration Options -->
            <div class="space-y-8 mb-10">
                <!-- Color Selection -->
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Colore
                        Disponibile</label>
                    <div class="flex gap-4">
                        <button
                            class="w-10 h-10 bg-black border-2 border-primary ring-2 ring-offset-2 ring-transparent transition-all"></button>
                        <button
                            class="w-10 h-10 bg-[#1B263B] border border-outline-variant/20 hover:border-primary transition-all"></button>
                        <button
                            class="w-10 h-10 bg-[#750005] border border-outline-variant/20 hover:border-primary transition-all"></button>
                    </div>
                </div>
                <!-- Size Selection -->
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Taglia
                        (EU Standard)</label>
                    <div class="grid grid-cols-5 gap-2">
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">S</button>
                        <button
                            class="py-3 border-2 border-primary font-mono text-xs bg-surface-container-lowest text-on-surface">M</button>
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">L</button>
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">XL</button>
                        <button
                            class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all">XXL</button>
                    </div>
                </div>
                <!-- Quantity -->
                <div class="flex items-center gap-6">
                    <div class="flex-1">
                        <label
                            class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Quantità</label>
                        <div class="flex items-center border border-outline-variant/20 w-32 h-12">
                            <button
                                class="w-10 h-full flex items-center justify-center hover:bg-surface-container transition-colors">
                                <span class="material-symbols-outlined text-sm">remove</span>
                            </button>
                            <input class="flex-1 border-none bg-transparent text-center font-mono text-sm focus:ring-0"
                                type="number" value="1" />
                            <button
                                class="w-10 h-full flex items-center justify-center hover:bg-surface-container transition-colors">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CTAs -->
            <div class="space-y-3">
                <button
                    class="w-full bg-primary-container text-on-primary py-5 px-8 font-bold text-sm tracking-widest uppercase flex items-center justify-center gap-3 transition-transform active:scale-[0.98]">
                    <span class="material-symbols-outlined text-lg" data-icon="shopping_cart">shopping_cart</span>
                    Aggiungi al Carrello
                </button>
                <button
                    class="w-full border border-on-surface/20 text-on-surface py-5 px-8 font-mono text-xs tracking-widest uppercase flex items-center justify-center gap-3 hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-lg" data-icon="description">description</span>
                    Scarica Scheda Tecnica
                </button>
            </div>
            <!-- Trust Badges -->
            <div
                class="mt-12 pt-8 border-t border-outline-variant/20 flex items-center gap-8 opacity-60 filter grayscale hover:grayscale-0 transition-all duration-500">
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 border border-on-surface/40 flex items-center justify-center text-[8px] font-bold">
                        ISO 9001</div>
                    <span class="text-[10px] font-mono leading-tight">Certificazione<br />Qualità</span>
                </div>
                <div class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 border border-on-surface/40 flex items-center justify-center text-[8px] font-bold">
                        EN 20471</div>
                    <span class="text-[10px] font-mono leading-tight">Standard<br />Sicurezza</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Technical Specs Section -->
    <section class="mt-24">
        <div class="mb-12">
            <h2 class="text-3xl font-black tracking-tighter uppercase mb-2">Specifiche Tecniche</h2>
            <div class="h-1 w-24 bg-primary"></div>
        </div>
        <div
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-px bg-outline-variant/20 border border-outline-variant/20">
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4" data-icon="water_drop">water_drop</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Impermeabilità
                </h3>
                <p class="font-mono text-xl font-bold">20.000 MM</p>
                <p class="text-xs text-secondary mt-2">Testato secondo standard ISO 811</p>
            </div>
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4" data-icon="air">air</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Traspirabilità
                </h3>
                <p class="font-mono text-xl font-bold">15.000 G/M²</p>
                <p class="text-xs text-secondary mt-2">Tecnologia Pro-Vent Active</p>
            </div>
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4" data-icon="layers">layers</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Composizione
                </h3>
                <p class="font-mono text-xl font-bold">100% NYLON RIPSTOP</p>
                <p class="text-xs text-secondary mt-2">Membrana in PTFE espanso</p>
            </div>
            <div class="bg-surface p-8">
                <span class="material-symbols-outlined text-primary mb-4"
                    data-icon="local_laundry_service">local_laundry_service</span>
                <h3 class="text-[10px] font-mono text-secondary uppercase tracking-widest mb-2">Manutenzione
                </h3>
                <p class="font-mono text-xl font-bold">40°C LAVAGGIO</p>
                <p class="text-xs text-secondary mt-2">Non utilizzare ammorbidenti</p>
            </div>
        </div>
        <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-12">
            <div class="space-y-4">
                <h4 class="font-bold text-lg border-b-2 border-surface-container inline-block pb-1">
                    Caratteristiche Costruttive</h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Cuciture termonastrate a triplo strato per isolamento
                            totale.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Zip YKK® Aquaguard® idrorepellenti ad alta resistenza.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Rinforzi in Cordura® su gomiti e zone ad alta usura.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-sm mt-1">check_circle</span>
                        <span class="text-sm">Cappuccio regolabile compatibile con caschi di protezione.</span>
                    </li>
                </ul>
            </div>
            <div class="bg-surface-container-low p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5">
                    <span class="material-symbols-outlined text-9xl">architecture</span>
                </div>
                <h4 class="font-bold text-lg mb-4">Note per la Personalizzazione</h4>
                <p class="text-sm leading-relaxed mb-6">
                    Il Guscio Pro-X supporta la stampa a caldo e il ricamo tecnico. Consigliamo il
                    posizionamento dei loghi aziendali sul petto sinistro o sulla schiena per mantenere
                    l'integrità della membrana impermeabile.
                </p>
                <a class="text-primary font-bold text-xs uppercase tracking-widest flex items-center gap-2 hover:gap-4 transition-all"
                    href="#">
                    Configura Logo Aziendale
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </section>

</x-layout>