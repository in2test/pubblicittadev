<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\PricingTier;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

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
     * This method calculates the correct unit price using the product's
     * pricing tiers, generates a unique quote number, and stores the
     * quote and its associated items in the database.
     *
     * @param  StoreQuoteRequest  $request  Validated request containing customer and product details.
     * @return RedirectResponse Redirects back to the product page with a success message.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // 1. Fetch product and find the applicable pricing tier for the requested quantity
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

        /** @var PricingTier|null $pricingTier */
        $unitPrice = $pricingTier->price_per_unit ?? $product->price;
        $subtotal = $unitPrice * $quantity;

        // 2. Generate a unique quote number (e.g., QT-20260506-001)
        $quoteNumber = sprintf(
            'QT-%s-%03d',
            now()->format('Ymd'),
            Quote::whereDate('created_at', now())->count() + 1
        );

        // 3. Create the Quote record
        $quote = Quote::create([
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

        // 4. Handle design file upload
        $designFilePath = null;
        if ($request->hasFile('design_file')) {
            $designFilePath = $request->file('design_file')->store('quote-designs');
        }

        // 5. Create the associated QuoteItem
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
