<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CartManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly CartManager $cart
    ) {}

    public function index(): View
    {
        return view('cart', [
            'items' => $this->cart->getItems(),
            'total' => $this->cart->total(),
            'count' => $this->cart->count(),
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color_id' => 'nullable|integer',
            'color_name' => 'nullable|string',
            'size_id' => 'nullable|integer',
            'size_name' => 'nullable|string',
            'print_placements' => 'nullable|string',  // Changed from array to string (JSON)
            'price' => 'required|numeric|min:0',
            'product_name' => 'required|string',
            'product_slug' => 'required|string',
            'image_url' => 'nullable|string',
        ]);

        // Decode print_placements JSON if it's a string
        $printPlacements = [];
        if (! empty($validated['print_placements'])) {
            $printPlacements = json_decode((string) $validated['print_placements'], true) ?? [];
        }

        $this->cart->add([
            'product_id' => $validated['product_id'],
            'product_name' => $validated['product_name'],
            'product_slug' => $validated['product_slug'],
            'image_url' => $validated['image_url'] ?? null,
            'color_id' => $validated['color_id'] ?? null,
            'color_name' => $validated['color_name'] ?? null,
            'size_id' => $validated['size_id'] ?? null,
            'size_name' => $validated['size_name'] ?? null,
            'print_placements' => $printPlacements,
            'price' => (float) $validated['price'],
            'quantity' => (int) $validated['quantity'],
        ]);

        return redirect()->route('cart')->with('success', 'Prodotto aggiunto al carrello!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cart->update($request->input('key'), (int) $request->input('quantity'));

        return back()->with('success', 'Carrello aggiornato!');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $this->cart->remove($request->input('key'));

        return back()->with('success', 'Prodotto rimosso dal carrello!');
    }

    public function clear()
    {
        $this->cart->clear();

        return back()->with('success', 'Carrello svuotato!');
    }
}
