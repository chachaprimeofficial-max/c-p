<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CurrencyService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function checkout(Request $request): View|RedirectResponse
    {
        abort_if($request->user()->is_admin, 403);
        if (empty(session('cart', []))) return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        return view('storefront.checkout', ['cart' => session('cart', [])]);
    }

    public function place(Request $request, CurrencyService $currencies, StripeCheckoutService $stripe): RedirectResponse
    {
        abort_if($request->user()->is_admin, 403);
        if (!$request->user()->email_verified_at) return redirect()->route('account.verify', ['email' => $request->user()->email]);
        if (empty(session('cart', []))) return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        if (!config('services.stripe.secret')) return back()->withErrors(['payment' => 'Card checkout is not enabled yet. The payment gateway must be configured and tested before accepting orders.']);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'shipping_country' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'shipping_state' => ['nullable', 'string', 'max:150'],
            'shipping_city' => ['required', 'string', 'max:150'],
            'shipping_postal_code' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $currency = $currencies->currentCurrency();
        $cart = session('cart', []);
        $order = null;
        try {
            $order = DB::transaction(function () use ($cart, $data, $currency, $request, $currencies) {
                $lines = [];
                $subtotal = 0.0;
                foreach ($cart as $productId => $quantity) {
                    $product = Product::with('priceTiers')->whereKey($productId)->where('status', 'published')->lockForUpdate()->firstOrFail();
                    $quantity = (int) $quantity;
                    if ($quantity < 1 || $quantity > $product->stock) throw new \RuntimeException('Stock changed for '.$product->name.'. Please update your cart.');
                    $sourcePrice = (float) $product->retail_price;
                    if ($product->b2b_enabled) {
                        $tier = $product->priceTiers->first(fn ($tier) => $quantity >= $tier->min_quantity && ($tier->max_quantity === null || $quantity <= $tier->max_quantity));
                        if ($tier) $sourcePrice = (float) $tier->unit_price;
                    }
                    $sourceCurrency = $tier?->currency ?? $product->base_currency ?? 'USD';
                    $converted = $currencies->convert($sourcePrice, $sourceCurrency, $currency);
                    if (!$converted['converted'] || $converted['currency'] !== $currency) throw new \RuntimeException('Live exchange rates are temporarily unavailable for '.$currency.'. Please try again later.');
                    $unitPrice = (float) $converted['amount'];
                    $lineTotal = round($unitPrice * $quantity, 2);
                    $subtotal += $lineTotal;
                    $lines[] = ['product' => $product, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'line_total' => $lineTotal];
                }
                $order = Order::create([
                    'user_id' => $request->user()->id, 'customer_name' => $data['customer_name'],
                    'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null,
                    'currency' => $currency, 'subtotal' => round($subtotal, 2), 'shipping_total' => 0,
                    'grand_total' => round($subtotal, 2), 'payment_provider' => 'stripe',
                    'payment_status' => 'pending', 'order_status' => 'pending_payment',
                    'shipping_country' => strtoupper($data['shipping_country']), 'shipping_state' => $data['shipping_state'] ?? null,
                    'shipping_city' => $data['shipping_city'], 'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                    'shipping_address' => $data['shipping_address'], 'customer_notes' => $data['customer_notes'] ?? 'Shipping charges and delivery time must be confirmed before fulfillment.',
                ]);
                foreach ($lines as $line) {
                    OrderItem::create([
                        'order_id' => $order->id, 'product_id' => $line['product']->id,
                        'product_name' => $line['product']->name, 'sku' => $line['product']->sku,
                        'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'],
                        'line_total' => $line['line_total'], 'currency' => $currency,
                        'attributes' => $line['product']->attributes,
                    ]);
                }
                return $order;
            });

            $session = $stripe->createSession($order, route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}', route('cart.index'));
            $order->update(['payment_reference' => $session['id']]);
            return redirect()->away($session['url']);
        } catch (Throwable $exception) {
            report($exception);
            if ($order && $order->payment_status === 'pending') $order->delete();
            return back()->withErrors(['payment' => $exception instanceof \RuntimeException ? $exception->getMessage() : 'Checkout could not be started. Please try again.'])->withInput();
        }
    }

    public function success(Request $request): View|RedirectResponse
    {
        abort_if($request->user()->is_admin, 403);
        $sessionId = (string) $request->query('session_id', '');
        $order = Order::with('items')->where('user_id', $request->user()->id)->where('payment_reference', $sessionId)->firstOrFail();
        return view('storefront.order-confirmation', compact('order'));
    }
}
