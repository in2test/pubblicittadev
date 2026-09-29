<section class="py-24 bg-surface-container-low">
        <div class="px-8 3xl:px-32 mx-auto">
            <div class="flex justify-between items-center mb-16">
                <h2 class="text-3xl font-black uppercase tracking-tight">OUTLET <span class="text-accent-500">Offerte</span>    </h2>
                <p class="text-primary font-mono font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1"
                ">
                Trova i migliori affari sui nostri prodotti selezionati.
            </p>
        </div>

        <x-product.grid :$products :onOutletPage=true/>
    </div>
</section>

