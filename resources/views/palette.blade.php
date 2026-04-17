<x-layout>
    <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <h1 class="text-4xl font-headline font-bold text-zinc-900 dark:text-zinc-100">Color Palette</h1>
            <p class="mt-4 text-lg text-zinc-500">Visualizing the custom theme colors defined in app.css</p>
        </div>

        @php
            $palettes = [
                'Highstyle' => 'highstyle',
                'Peach Souffle' => 'peachsouffle',
                'Summer Day' => 'summerday',
                'Deep Cove' => 'deepcove',
                'Vivid Auburn' => 'vividauburn',
                'Gray' => 'gray',
            ];
            $shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
        @endphp

        <div class="space-y-16">
            <!-- Tailwind v4 Scanner Safelist
                bg-highstyle-50 bg-highstyle-100 bg-highstyle-200 bg-highstyle-300 bg-highstyle-400 bg-highstyle-500 bg-highstyle-600 bg-highstyle-700 bg-highstyle-800 bg-highstyle-900 bg-highstyle-950
                bg-peachsouffle-50 bg-peachsouffle-100 bg-peachsouffle-200 bg-peachsouffle-300 bg-peachsouffle-400 bg-peachsouffle-500 bg-peachsouffle-600 bg-peachsouffle-700 bg-peachsouffle-800 bg-peachsouffle-900 bg-peachsouffle-950
                bg-summerday-50 bg-summerday-100 bg-summerday-200 bg-summerday-300 bg-summerday-400 bg-summerday-500 bg-summerday-600 bg-summerday-700 bg-summerday-800 bg-summerday-900 bg-summerday-950
                bg-deepcove-50 bg-deepcove-100 bg-deepcove-200 bg-deepcove-300 bg-deepcove-400 bg-deepcove-500 bg-deepcove-600 bg-deepcove-700 bg-deepcove-800 bg-deepcove-900 bg-deepcove-950
                bg-vividauburn-50 bg-vividauburn-100 bg-vividauburn-200 bg-vividauburn-300 bg-vividauburn-400 bg-vividauburn-500 bg-vividauburn-600 bg-vividauburn-700 bg-vividauburn-800 bg-vividauburn-900 bg-vividauburn-950
                bg-gray-50 bg-gray-100 bg-gray-200 bg-gray-300 bg-gray-400 bg-gray-500 bg-gray-600 bg-gray-700 bg-gray-800 bg-gray-900 bg-gray-950
            -->
            @foreach ($palettes as $name => $prefix)
                <div>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mb-6 font-headline">{{ $name }}</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-11 gap-4">
                        @foreach ($shades as $shade)
                            <div class="flex flex-col items-center sm:items-start text-center sm:text-left">
                                <div class="h-12 w-12 rounded shadow-sm border border-zinc-200 dark:border-zinc-800 bg-{{ $prefix }}-{{ $shade }}"></div>
                                <div class="mt-2 text-xs font-mono text-zinc-600 dark:text-zinc-400 font-medium">
                                    {{ $shade }}
                                </div>
                                <div class="text-[9px] font-mono text-zinc-400 dark:text-zinc-500 truncate w-full" title="bg-{{ $prefix }}-{{ $shade }}">
                                    bg-{{ $prefix }}-{{ $shade }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layout>
