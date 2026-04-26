<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importazione Batch NewWave</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12">
        <div class="max-w-5xl mx-auto px-4">
            <!-- Header -->
            <div class="mb-8">
                <a href="/admin/products/new-wave-products" class="text-primary-600 hover:text-primary-700 text-sm flex items-center gap-1 mb-2">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Torna ai prodotti
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Importazione Batch NewWave
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Inserisci più codici SKU per importarli in blocco
                </p>
            </div>

            @if(isset($error))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600">{{ $error }}</p>
                </div>
            @endif

            @if($step === 'imported')
                <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Importazione completata
                    </h2>

                    @if(!empty($importResults['imported']))
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg mb-4">
                            <h3 class="font-medium text-green-800">Prodotti importati ({{ count($importResults['imported']) }})</h3>
                            <p class="text-green-600 mt-1">{{ implode(', ', $importResults['imported']) }}</p>
                            <p class="text-sm text-green-500 mt-2">
                                La sincronizzazione partirà automaticamente in background.
                            </p>
                        </div>
                    @endif

                    @if(!empty($importResults['errors']))
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
                            <h3 class="font-medium text-red-800">Errori ({{ count($importResults['errors']) }})</h3>
                            <ul class="text-red-600 mt-1 list-disc list-inside">
                                @foreach($importResults['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <a href="{{ route('batch-import') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Importa altri prodotti
                    </a>
                </div>

            @elseif($step === 'validate' && !empty($validatedProducts))
                <form action="{{ route('batch-import') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="skus" value="{{ $skus }}">
                    <input type="hidden" name="category_id" value="{{ $selectedCategory }}">
                    
                    <!-- Category Selection -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Categoria
                        </h2>
                        
                        <div class="flex gap-4 items-start">
                            <div class="flex-1">
                                <select name="category_id" id="category_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                                    <option value="">-- Seleziona categoria --</option>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ $selectedCategory == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" onclick="toggleNewCategory()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                                + Nuova
                            </button>
                        </div>

                        <!-- New Category Form (hidden by default) -->
                        <div id="newCategoryForm" class="hidden mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Nuova Categoria</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome</label>
                                    <input type="text" name="new_category_name" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoria padre</label>
                                    <select name="new_category_parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">-- Nessuna --</option>
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Risultati verifica ({{ count($validatedProducts) }} prodotti)
                        </h2>

                        <div class="overflow-x-auto">
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
                                    @foreach($validatedProducts as $index => $product)
                                        <tr class="{{ $product['exists'] ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                                            <td class="px-4 py-3 text-sm font-mono">{{ $product['sku'] }}</td>
                                            <td class="px-4 py-3 text-sm">{{ $product['name'] }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($product['price'])
                                                    €{{ number_format($product['price'], 2) }}
                                                @else
                                                    --
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($product['exists'])
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Già importato
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Disponibile
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if(!$product['exists'])
                                                    <input type="checkbox" name="selected[{{ $product['sku'] }}]" value="1" {{ $product['selected'] ? 'checked' : '' }} class="w-4 h-4 rounded">
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if(!empty($invalidSkus))
                            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    Codici non validi ({{ count($invalidSkus) }})
                                </h4>
                                <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ implode(', ', $invalidSkus) }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">
                            Importa selezionati
                        </button>
                        <a href="{{ route('batch-import') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200">
                            Annulla
                        </a>
                    </div>
                </form>

            @else
                <!-- SKU Input Form -->
                <form action="{{ route('batch-import') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="bg-white rounded-xl border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Inserisci codici SKU
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Inserisci uno o più codici SKU separati da spazi, virgola o punto e virgola.
                        </p>

                        <div>
                            <label for="skus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Codici SKU
                            </label>
                            <textarea
                                name="skus"
                                id="skus"
                                rows="6"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                placeholder="es. NWG-12345, NWG-67890, NWG-11111"
                            >{{ $skus ?? '' }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Separa i codici con spazi, virgole o punto e virgola.
                            </p>
                        </div>

                        <button type="submit" class="mt-4 px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">
                            Verifica codici
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        function toggleNewCategory() {
            const form = document.getElementById('newCategoryForm');
            form.classList.toggle('hidden');
        }
    </script>
</body>
</html>