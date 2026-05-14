<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\RedirectResponse;

/**
 * QuoteController handles the generation and storage of product quotes.
 *
 * It manages the process of creating a quote request, calculating the
 * unit price based on pricing tiers, and associating design files.
 */
class QuoteController extends Controller
{
    /**
     * Store a new quote request.
     *
     * @param  StoreQuoteRequest  $request  Validated request containing customer and product details.
     * @return RedirectResponse Redirects back to the product page with a success message.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $product = Product::findOrFail($validated['product_id']);
        $quantity = (int) $validated['quantity'];
        $unitPrice = $product->getPriceForQuantity($quantity);
        $subtotal = $unitPrice * $quantity;

        // 1. Generate a unique quote number (e.g., QT-20260506-001)
        $quoteNumber = sprintf(
            'QT-%s-%03d',
            now()->format('Ymd'),
            Quote::whereDate('created_at', now())->count() + 1
        );

        // 2. Create the Quote record
        $quote = Quote::create([
            'user_id' => auth()->id(),
            'quote_number' => $quoteNumber,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_whatsapp' => $validated['customer_whatsapp'] ?? null,
            'total_items' => $quantity,
            'total_price' => $subtotal,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        // 3. Handle design file upload
        $designFilePath = $request->hasFile('design_file')
            ? $request->file('design_file')->store('quote-designs')
            : null;

        // 4. Create the associated QuoteItem
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

        return back()->with('quoteSuccess', 'Richiesta di preventivo inviata correttamente. Il nostro team ti contatterà a breve.');
    }
}
