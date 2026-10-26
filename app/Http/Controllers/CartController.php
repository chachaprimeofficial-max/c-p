<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartPricingService;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartPricingService $pricing, CurrencyService $currencies): View
    {
        $lines = $pricing->lines(session('cart', []), $currencies);
        $subtotal = array_sum(array_column($lines, 'line_total'));
        $currency = $lines[0]['currency'] ?? $currencies->currentCurrency();
        return view('storefront.cart', compact('lines', 'subtotal', 'currency'));
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published', 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10000']]);
        $cart = session('cart', []);
        $quantity = (int) ($cart[$product->id] ?? 0) + (int) $data['quantity'];
        if ($quantity > $product->stock) return back()->withErrors(['cart' => 'The requested quantity is greater than available stock.']);
        $cart[$product->id] = $quantity;
        session(['cart' => $cart]);
        return redirect()->route('cart.index')->with('success', 'Product added to your cart.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['quantities' => ['required', 'array'], 'quantities.*' => ['required', 'integer', 'min:1', 'max:10000']]);
        $cart = session('cart', []);
        foreach ($data['quantities'] as $id => $quantity) {
            $product = Product::whereKey($id)->where('status', 'published')->first();
            if (!$product) { unset($cart[$id]); continue; }
            if ((int) $quantity > $product->stock) return back()->withErrors(['cart' => 'One or more quantities exceed available stock.']);
            $cart[$id] = (int) $quantity;
        }
        session(['cart' => $cart]);
        return back()->with('success', 'Cart updated.');
    }

    public function remove(int $productId): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$productId]);
        session(['cart' => $cart]);
        return redirect()->route('cart.index')->with('success', 'Item removed from your cart.');
    }
}
