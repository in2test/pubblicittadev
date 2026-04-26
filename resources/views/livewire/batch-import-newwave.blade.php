<div>
    @if (!$validated && !$imported)
        <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Inserisci codici SKU
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Inserisci uno o più codici SKU separati da spazi, virgola o punto e virgola.
            </p>

            <form wire:submit.prevent="validateSkus" class="space-y-4">
                <div>
                    <label for="skus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Codici SKU
                    </label>
                    <textarea
                        wire:model="skus"
                        id="skus"
                        rows="6"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm"
                        placeholder="es. NWG-12345, NWG-67890, NWG-11111"
                    ></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Separa i codici con spazi, virgole o punto e virgola.
                    </p>
                </div>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                    Verifica codici
                </button>
            </form>
        </div>
    @endif

    @if ($validated && !empty($validatedProducts))
        <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Risultati verifica ({{ count($validatedProducts) }} prodotti)
            </h3>

            <div class="overflow-x-auto mt-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prezzo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stato</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Importa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($validatedProducts as $index => $product)
                            <tr class="{{ $product['exists'] ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                                <td class="px-4 py-3 text-sm font-mono">{{ $product['sku'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $product['name'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($product['price'])
                                        €{{ number_format($product['price'], 2) }}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($product['exists'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            Già importato
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Disponibile
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if (!$product['exists'])
                                        <input
                                            type="checkbox"
                                            wire:model="validatedProducts.{{ $index }}.selected"
                                            class="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
                                        />
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (!empty($invalidSkus))
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Codici non validi ({{ count($invalidSkus) }})
                    </h4>
                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                        {{ implode(', ', $invalidSkus) }}
                    </p>
                </div>
            @endif

            <div class="mt-4 flex gap-3">
                <button wire:click="importSelected" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                    Importa selezionati
                </button>
                <button wire:click="resetForm" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200">
                    Annulla
                </button>
            </div>
        </div>
    @endif

    @if ($imported && !empty($importResults))
        <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Importazione completata
            </h3>

            @if (!empty($importResults['imported']))
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
                        Prodotti importati ({{ count($importResults['imported']) }})
                    </h4>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                        {{ implode(', ', $importResults['imported']) }}
                    </p>
                    <p class="text-xs text-green-500 mt-2">
                        La sincronizzazione partirà automaticamente in background.
                    </p>
                </div>
            @endif

            @if (!empty($importResults['errors']))
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Errori ({{ count($importResults['errors']) }})
                    </h4>
                    <ul class="text-sm text-red-600 dark:text-red-400 mt-1 list-disc list-inside">
                        @foreach ($importResults['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4 flex gap-3">
                <button wire:click="resetForm" class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200">
                    Importa altri prodotti
                </button>
            </div>
        </div>
    @endif
</div>