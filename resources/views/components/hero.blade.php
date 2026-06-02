@props(['slides'])

<!-- Hero Section: Asymmetric Split -->
<section class="relative min-h-[600px] flex flex-col lg:flex-row overflow-hidden lg:h-[calc(100vh-80px)] mt-20 lg:mt-0">
    <!-- Left Side: Typography Content -->
    <div class="w-full lg:w-1/2 bg-gray-50 flex items-center px-8 3xl:px-32 py-14 relative z-10">
        <div class="max-w-4xl">
            <div class="flex items-center gap-4 mb-8">
                <span class="font-mono text-xs tracking-[0.3em] uppercase text-accent-500 font-bold">Stampa, grafica e personalizzazione dal 1990</span>
                <div class="h-px w-12 bg-accent-500/30"></div>
            </div>
            <h1 class="text-5xl lg:text-7xl xl:text-8xl font-black tracking-tighter text-gray-950 leading-[0.9] mb-10">
                STAMPA PERFETTA, FILE O NO <span class="text-accent-700">TI GUIDIAMO NOI.</span>
            </h1>
            <p class="text-lg lg:text-xl text-gray-900 mb-12 font-light leading-relaxed">
                Hai già il file pronto? Lo controlliamo gratuitamente e stampiamo in tempi rapidi. Non ce l’hai ancora?
                Ti aiutiamo a creare la grafica giusta e a trasformarla in un prodotto pronto da usare.
                <br>
                Con PubbliCittà24 hai un riferimento diretto, umano e concreto: niente passaggi complicati, niente dubbi
                tecnici lasciati a te, solo soluzioni chiare per stampare bene.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('catalog') }}"
                    class="bg-accent-500 hover:bg-accent-700 text-gray-50 px-8 py-4 text-sm font-bold tracking-widest uppercase transition-all flex items-center gap-3">
                    VEDI CATALOGO
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
                <a href="https://wa.me/393278564120" target="_blank"
                    class="bg-gray-950 hover:bg-gray-700 text-gray-50 px-8 py-4 text-sm font-bold tracking-widest uppercase transition-all flex items-center gap-3">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp info
                </a>
            </div>
        </div>
        <!-- Technical Background Decals -->
        <div class="absolute top-24 right-0 pointer-events-none opacity-5 select-none">
            <span class="text-9xl font-black text-gray-950">PUBBLICITTA24</span>
        </div>
    </div>
    <!-- Right Side: Imagery -->
    <div class="w-full lg:w-1/2 relative bg-gray-950 group overflow-hidden" x-data="{
        activeSlide: 0,
        slides: {{ Js::from($slides) }},
        init() {
            setInterval(() => {
                this.activeSlide = this.activeSlide === this.slides.length - 1 ? 0 : this.activeSlide + 1;
            }, 5000);
        }
    }">
        <template x-for="(slide, index) in slides" :key="index">
            <div x-show="activeSlide === index"
                 x-transition:enter="transition-opacity ease-in-out duration-1000"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in-out duration-1000"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full">
                <img :src="slide.img"
                    class="object-cover grayscale brightness-75 dark:brightness-100 group-hover:grayscale-0 group-hover:brightness-100 transition-all duration-300 w-full h-full"
                    :alt="slide.label" />
                <!-- Overlay Elements -->
                <div class="absolute inset-0 bg-linear-to-t from-gray-900/60 to-transparent"></div>
                <div class="absolute bottom-12 right-8 3xl:right-32 text-gray-50 text-right">
                    <div class="font-mono text-xs mb-2 opacity-60" x-text="slide.sub"></div>
                    <div class="text-2xl font-black tracking-tight italic" x-text="slide.label"></div>
                </div>
            </div>
        </template>
        <!-- Floating Card (Static on top of carousel) -->
        <div class="absolute top-20 left-12 lg:left-20 bg-gray-50/80 p-6 shadow-2xl max-w-xs hidden md:block backdrop-blur-sm z-20">
            <div class="font-mono text-[10px] text-accent-500 mb-4">SYSTEM_STATUS: ACTIVE</div>
            <h3 class="text-xl font-bold mb-2 text-gray-900">Abbigliamento Premium</h3>
            <p class="text-sm text-gray-700 mb-4 leading-snug">Materiali certificati e stampe ultra-resistenti per ogni settore lavorativo.</p>
            <div class="flex justify-between items-center border-t border-accent-500 pt-4">
                <span class="font-mono text-xs text-gray-900">Qualità Certificata</span>
                <span class="material-symbols-outlined text-accent-500">verified_user</span>
            </div>
        </div>
    </div>
</section>
