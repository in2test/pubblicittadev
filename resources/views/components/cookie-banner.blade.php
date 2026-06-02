@props(['policyUrl' => '/privacy'])

<div x-data="{
        show: false,
        init() {
            if (!localStorage.getItem('cookie_consent')) {
                setTimeout(() => this.show = true, 1000);
            }
        },
        acceptAll() {
            localStorage.setItem('cookie_consent', 'all');
            this.updateGtagConsent('granted');
            this.show = false;
        },
        acceptEssential() {
            localStorage.setItem('cookie_consent', 'essential');
            this.show = false;
        },
        updateGtagConsent(status) {
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': status
                });
            }
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-full"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-full"
    class="fixed bottom-0 left-0 right-0 z-50 p-4 sm:p-6"
    style="display: none;">
    
    <div class="mx-auto max-w-7xl bg-gray-50 border-2 border-gray-950 p-6 shadow-2xl flex flex-col md:flex-row items-center gap-6 justify-between">
        <div class="flex-1">
            <h3 class="text-lg font-black uppercase tracking-tight text-gray-950 mb-2">Utilizziamo i cookie</h3>
            <p class="text-sm font-mono text-gray-500">
                Questo sito utilizza cookie essenziali per garantirne il corretto funzionamento e cookie di tracciamento per capire come interagisci con esso. 
                <a href="{{ $policyUrl }}" class="text-secondary font-bold hover:underline">Scopri di più</a>.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3 shrink-0">
            <button @click="acceptAll()" class="w-full sm:w-auto bg-secondary text-gray-50 px-8 py-3 font-black border-2 border-gray-950 uppercase tracking-widest text-xs hover:bg-gray-950 transition-colors whitespace-nowrap">
                Accetta Tutto
            </button>
            <button @click="acceptEssential()" class="w-full sm:w-auto bg-gray-50 border-2 border-gray-950 text-gray-950 px-8 py-3 font-black uppercase tracking-widest text-xs hover:bg-gray-100 transition-colors whitespace-nowrap">
                Solo Essenziali
            </button>
        </div>
    </div>
</div>
