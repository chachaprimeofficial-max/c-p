<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\GroupBuyCampaign;
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
        $currency = $currencies->currentCurrency();
        $pricingAvailable = collect($lines)->every(fn ($line) => $line['currency'] === $currency && $line['converted']);
        if (!$pricingAvailable) $subtotal = null;
        return view('storefront.cart', compact('lines', 'subtotal', 'currency', 'pricingAvailable'));
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published', 404);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'campaign_id' => ['nullable', 'integer', 'exists:group_buy_campaigns,id'],
        ]);
        $product->loadMissing('variants');
        $variant = null;
        if (!empty($data['variant_id'])) {
            $variant = ProductVariant::whereKey($data['variant_id'])->where('product_id', $product->id)->firstOrFail();
        } elseif ($product->variants->isNotEmpty()) {
            return back()->withErrors(['variant_id' => 'Choose a product variation before adding this item to your cart.']);
        }
        $campaign = null;
        if (!empty($data['campaign_id'])) {
            $campaign = GroupBuyCampaign::whereKey($data['campaign_id'])->where('product_id', $product->id)->firstOrFail();
            $availableNow = $campaign->status === 'active'
                && (!$campaign->starts_at || $campaign->starts_at->isPast())
                && (!$campaign->ends_at || $campaign->ends_at->isFuture())
                && $campaign->current_participants < $campaign->target_participants;
            if (!$availableNow) return back()->withErrors(['campaign_id' => 'This group-buy offer is no longer available. Please choose standard pricing or another active offer.']);
        }
        $cartKey = $campaign
            ? $product->id.':'.($variant ? $variant->id : 0).':'.$campaign->id
            : ($variant ? $product->id.':'.$variant->id : (string) $product->id);
        $available = $variant ? (int) $variant->stock : (int) $product->stock;
        $cart = session('cart', []);
        $quantity = (int) ($cart[$cartKey] ?? 0) + (int) $data['quantity'];
        if ($quantity > $available) return back()->withErrors(['cart' => 'The requested quantity is greater than available stock for this option.']);
        $cart[$cartKey] = $quantity;
        session(['cart' => $cart]);
        return redirect()->route('cart.index')->with('success', 'Product added to your cart.');
    }

    public function update(Request $request, CartPricingService $pricing): RedirectResponse
    {
        $data = $request->validate(['quantities' => ['required', 'array'], 'quantities.*' => ['required', 'integer', 'min:1', 'max:10000']]);
        $cart = session('cart', []);
        foreach ($data['quantities'] as $key => $quantity) {
            [$productId, $variantId] = $pricing->parseKey((string) $key);
            $product = $productId ? Product::whereKey($productId)->where('status', 'published')->first() : null;
            if (!$product) { unset($cart[$key]); continue; }
            $variant = null;
            if ($variantId) {
                $variant = ProductVariant::whereKey($variantId)->where('product_id', $product->id)->first();
                if (!$variant) { unset($cart[$key]); continue; }
            }
            $available = $variant ? (int) $variant->stock : (int) $product->stock;
            if ((int) $quantity > $available) return back()->withErrors(['cart' => 'One or more quantities exceed available stock for the selected option.']);
            $cart[$key] = (int) $quantity;
        }
        session(['cart' => $cart]);
        return back()->with('success', 'Cart updated.');
    }

    public function remove(string $cartKey): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$cartKey]);
        session(['cart' => $cart]);
        return redirect()->route('cart.index')->with('success', 'Item removed from your cart.');
    }
}
