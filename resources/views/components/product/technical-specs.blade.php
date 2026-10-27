@props(['product'])

@php
    /** @var \App\Models\Product $product */
    $careDetails = $product->getCareInstructionsDetails();
    $featureDetails = $product->getConstructionFeaturesDetails();
    $certDetails = $product->getCertificationsDetails();
    $isCareNotes = $product->isCareInstructionNotes();
    
    $hasFabrics = ! empty($product->technical_specs);
    $hasFeatures = ! empty($featureDetails);
    $hasCerts = ! empty($certDetails);
    $hasCare = $isCareNotes && ! empty($careDetails);
    $hasCustomization = ! $isCareNotes && ! empty($product->customization_notes);

    $genderLabels = [
        'woman' => 'Donna',
        'man' => 'Uomo',
        'baby' => 'Bambino',
        'unisex' => 'Unisex',
    ];

    $genderKeys = is_array($product->genders) ? $product->genders : [];
    if ($genderKeys === []) {
        $name = (string) $product->name;
        if (str_contains($name, 'Women') || str_contains($name, 'Donna') || str_contains($name, 'Lady') || str_contains($name, 'Ladies')) {
            $genderKeys = ['woman'];
        } elseif (str_contains($name, 'Junior') || str_contains($name, 'Bambin') || str_contains($name, 'Kid')) {
            $genderKeys = ['baby'];
        } else {
            $genderKeys = ['unisex'];
        }
    }
    $hasGender = ! empty($genderKeys);
@endphp

@if ($hasFabrics || $hasFeatures || $hasCerts || $hasCare || $hasCustomization || $hasGender)
    <section class="mt-16 px-8 py-16 3xl:px-32 bg-gray-50 border-t-4 border-gray-950">
        <div class="max-w-7xl mx-auto">
            <div class="mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-200 text-gray-800 text-[11px] font-mono uppercase tracking-widest rounded mb-3">
                    <span class="material-symbols-outlined text-sm">verified_user</span>
                    Informazioni Dettagliate
                </div>
                <h2 class="text-3xl lg:text-4xl font-black tracking-tight uppercase text-gray-950">
                    Specifiche e Dettagli del Capo
                </h2>
                <div class="h-1 w-20 bg-primary mt-2"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Left Column: Fabrics & Features --}}
                <div class="lg:col-span-7 flex flex-col gap-8">
                    @if ($hasGender)
                        <div class="bg-gray-50 p-6 lg:p-8 rounded-lg border border-gray-200 shadow-xs">
                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-2xl">person</span>
                                <div>
                                    <h3 class="text-xs font-mono uppercase tracking-widest text-secondary font-bold">
                                        Genere
                                    </h3>
                                    <p class="text-sm font-semibold text-gray-900">Per chi è pensato</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($genderKeys as $genderKey)
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-primary/10 text-primary border border-primary/20 text-xs font-bold uppercase tracking-wider">
                                        {{ $genderLabels[$genderKey] ?? ucfirst($genderKey) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($hasFabrics)
                        <div class="bg-gray-50 p-6 lg:p-8 rounded-lg border border-gray-200 shadow-xs">
                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-2xl">layers</span>
                                <div>
                                    <h3 class="text-xs font-mono uppercase tracking-widest text-secondary font-bold">
                                        Composizione & Materiali
                                    </h3>
                                    <p class="text-sm font-semibold text-gray-900">Tessuto e fibre certificate</p>
                                </div>
                            </div>
                            <div class="prose prose-sm prose-gray max-w-none text-gray-800 font-medium leading-relaxed">
                                {!! nl2br(e($product->technical_specs)) !!}
                            </div>
                        </div>
                    @endif

                    @if ($hasFeatures)
                        <div class="bg-gray-50 p-6 lg:p-8 rounded-lg border border-gray-200 shadow-xs">
                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-2xl">construction</span>
                                <div>
                                    <h3 class="text-xs font-mono uppercase tracking-widest text-secondary font-bold">
                                        Caratteristiche del Capo
                                    </h3>
                                    <p class="text-sm font-semibold text-gray-900">Finiture tecniche e funzionalità</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($featureDetails as $feat)
                                    <div class="flex items-start gap-3 p-3 rounded-md bg-gray-50 border border-gray-100">
                                        <span class="material-symbols-outlined text-primary text-lg mt-0.5 shrink-0">{{ $feat['icon'] }}</span>
                                        <div>
                                            <div class="text-xs font-bold text-gray-950 leading-snug">{{ $feat['label'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($hasCustomization)
                        <div class="bg-gray-50 p-6 lg:p-8 rounded-lg border border-gray-200 shadow-xs">
                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-2xl">draw</span>
                                <div>
                                    <h3 class="text-xs font-mono uppercase tracking-widest text-secondary font-bold">
                                        Note di Personalizzazione
                                    </h3>
                                    <p class="text-sm font-semibold text-gray-900">Consigli per stampa e ricamo</p>
                                </div>
                            </div>
                            <div class="prose prose-sm prose-gray max-w-none text-gray-800 leading-relaxed">
                                {!! nl2br(e($product->customization_notes)) !!}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right Column: Certifications & Care Instructions --}}
                <div class="lg:col-span-5 flex flex-col gap-8">
                    @if ($hasCerts)
                        <div class="bg-gray-50 p-6 lg:p-8 rounded-lg border border-gray-200 shadow-xs">
                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                <span class="material-symbols-outlined text-emerald-600 text-2xl">verified</span>
                                <div>
                                    <h3 class="text-xs font-mono uppercase tracking-widest text-secondary font-bold">
                                        Certificazioni di Qualità
                                    </h3>
                                    <p class="text-sm font-semibold text-gray-900">Standard etici e di sostenibilità</p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                @foreach ($certDetails as $cert)
                                    <div class="flex items-center gap-3.5 p-3.5 rounded-lg bg-emerald-50/60 border border-emerald-200/70 text-emerald-950">
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-xl">{{ $cert['icon'] }}</span>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold">{{ $cert['label'] }}</div>
                                            <div class="text-[11px] text-emerald-800 font-mono">Conforme alle normative UE</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($hasCare)
                        <div class="bg-gray-50 p-6 lg:p-8 rounded-lg border border-gray-200 shadow-xs">
                            <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-3">
                                <span class="material-symbols-outlined text-primary text-2xl">local_laundry_service</span>
                                <div>
                                    <h3 class="text-xs font-mono uppercase tracking-widest text-secondary font-bold">
                                        Istruzioni di Lavaggio e Cura
                                    </h3>
                                    <p class="text-sm font-semibold text-gray-900">Mantieni inalterati colore e stampe</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($careDetails as $care)
                                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 border border-gray-100 transition-colors hover:bg-gray-100/60" title="{{ $care['label'] }}">
                                        <div class="w-9 h-9 rounded-md bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-900 shrink-0 shadow-xs">
                                            <x-icons.care-symbol :symbol="$care['symbol']" class="w-5 h-5 text-gray-800" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-950 leading-tight">
                                                {{ $care['short'] }}
                                            </div>
                                            <div class="text-[10px] text-gray-500 truncate" title="{{ $care['label'] }}">
                                                {{ $care['label'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
