<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SyncStatus;
use App\Jobs\SyncNewWaveProductJob;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductAvailabilityService;
use App\Support\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class BatchImportController extends Controller
{
    public function index(): View
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        return view('batch-import', [
            'step' => 'input',
            'categories' => Category::pluck('name', 'id')->all(),
            'selectedCategory' => null,
            'skus' => '',
            'validatedProducts' => [],
            'invalidSkus' => [],
            'importResults' => [],
        ]);
    }

    public function validate(Request $request): View
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $categoryId = $request->input('category_id');
        $skus = array_filter(
            array_map(trim(...), preg_split('/[\s,;]+/', (string) $request->input('skus', ''))));

        $service = app(ProductAvailabilityService::class);
        $validatedProducts = [];
        $invalidSkus = [];

        if ($skus !== []) {
            $results = $service->validateSkus(array_values($skus));

            foreach ($results['valid'] as $sku => $info) {
                $validatedProducts[] = [
                    'sku' => $sku,
                    'name' => $info['name'],
                    'price' => $info['price'],
                    'exists' => Product::where('sku', $sku)->exists(),
                    'selected' => true,
                ];
            }
            $invalidSkus = $results['invalid'];
        }

        return view('batch-import', [
            'step' => 'validate',
            'categories' => Category::pluck('name', 'id')->all(),
            'selectedCategory' => $categoryId,
            'skus' => $request->input('skus', ''),
            'validatedProducts' => $validatedProducts,
            'invalidSkus' => $invalidSkus,
            'importResults' => [],
        ]);
    }

    public function import(Request $request): View
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $categoryId = $request->input('category_id');
        $selectedSkus = $request->input('selected', []);

        if (empty($selectedSkus)) {
            return view('batch-import', [
                'step' => 'validate',
                'categories' => Category::pluck('name', 'id')->all(),
                'selectedCategory' => $categoryId,
                'skus' => $request->input('skus', ''),
                'validatedProducts' => [],
                'invalidSkus' => [],
                'importResults' => [],
                'error' => 'Seleziona almeno un prodotto da importare.',
            ]);
        }

        $service = app(ProductAvailabilityService::class);
        $imported = [];
        $errors = [];

        $results = $service->validateSkus(array_keys($selectedSkus));

        foreach ($selectedSkus as $sku => $selected) {
            if (! $selected) {
                continue;
            }
            if (Product::where('sku', $sku)->exists()) {
                continue;
            }

            try {
                $info = $results['valid'][$sku] ?? ['name' => $sku, 'price' => 0];

                $product = Product::create([
                    'name' => $info['name'],
                    'sku' => $sku,
                    'slug' => SlugGenerator::unique(Product::class, $sku),
                    'type' => Product::TYPE_NEWWAVE,
                    'price' => $info['price'] ?? 0,
                    'category_id' => $categoryId,
                    'sync_status' => SyncStatus::Pending,
                    'is_active' => false,
                ]);

                SyncNewWaveProductJob::dispatch($product);

                $imported[] = $sku;
            } catch (Throwable $e) {
                $errors[] = $sku.': '.$e->getMessage();
            }
        }

        return view('batch-import', [
            'step' => 'imported',
            'categories' => Category::pluck('name', 'id')->all(),
            'selectedCategory' => $categoryId,
            'skus' => '',
            'validatedProducts' => [],
            'invalidSkus' => [],
            'importResults' => [
                'imported' => $imported,
                'errors' => $errors,
            ],
        ]);
    }

    public function createCategory(Request $request): RedirectResponse
    {
        $category = Category::create([
            'name' => $request->input('name'),
            'slug' => SlugGenerator::unique(Category::class, $request->input('name')),
            'parent_id' => $request->input('parent_id'),
            'is_active' => true,
        ]);

        return back()->with('created_category_id', $category->id);
    }
}
