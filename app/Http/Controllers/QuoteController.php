<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;

class QuoteController extends Controller
{
    public function store(StoreQuoteRequest $request)
    {
        $validated = $request->validated();

        $product = Product::with('pricingTiers')->findOrFail($validated['product_id']);
        $quantity = $validated['quantity'];

        $pricingTier = $product->pricingTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })
            ->orderByDesc('min_quantity')
            ->first();

        $unitPrice = $pricingTier?->price_per_unit ?? $product->price;
        $subtotal = $unitPrice * $quantity;

        $quoteNumber = sprintf('QT-%s-%03d', now()->format('Ymd'), Quote::whereDate('created_at', now())->count() + 1);

        $quote = Quote::create([
            'quote_number' => $quoteNumber,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'customer_whatsapp' => $validated['customer_whatsapp'],
            'total_items' => $quantity,
            'total_price' => $subtotal,
            'status' => 'pending',
            'notes' => $validated['notes'],
        ]);

        $designFilePath = null;

        if ($request->hasFile('design_file')) {
            $designFilePath = $request->file('design_file')->store('quote-designs');
        }

        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'color_id' => $validated['color_id'] ?? null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'customization_json' => $validated['customization_points'] ?? [],
            'design_file_path' => $designFilePath,
        ]);

        return redirect()
            ->back()
            ->with('quoteSuccess', 'Richiesta di preventivo inviata correttamente. Il nostro team ti contatterà a breve.');
    }
}
