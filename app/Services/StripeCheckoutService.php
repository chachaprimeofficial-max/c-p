<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeCheckoutService
{
    private const SUPPORTED_CURRENCIES = ['AED', 'AUD', 'CAD', 'CHF', 'CNY', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SAR', 'SEK', 'SGD', 'TRY', 'USD'];

    public function createSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') throw new RuntimeException('Card payments are not configured. Add STRIPE_SECRET_KEY to the server environment.');
        $currency = strtoupper($order->currency);
        if (!in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            throw new RuntimeException('This display currency is not enabled for card charges by the configured gateway. Choose AED, CNY, EUR, GBP, JPY, SAR, TRY or USD for checkout.');
        }

        $order->loadMissing('items');
        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $order->email,
            'client_reference_id' => $order->order_number,
            'metadata[order_id]' => (string) $order->id,
            'payment_intent_data[metadata][order_id]' => (string) $order->id,
        ];
        foreach ($order->items as $index => $item) {
            $prefix = 'line_items['.$index.']';
            $payload[$prefix.'[price_data][currency]'] = strtolower($currency);
            $payload[$prefix.'[price_data][unit_amount]'] = $this->minorUnits((float) $item->unit_price, $currency);
            $payload[$prefix.'[price_data][product_data][name]'] = mb_substr($item->product_name, 0, 250);
            $payload[$prefix.'[quantity]'] = $item->quantity;
        }
        if ((float) $order->shipping_total > 0) {
            $index = $order->items->count();
            $prefix = 'line_items['.$index.']';
            $payload[$prefix.'[price_data][currency]'] = strtolower($currency);
            $payload[$prefix.'[price_data][unit_amount]'] = $this->minorUnits((float) $order->shipping_total, $currency);
            $payload[$prefix.'[price_data][product_data][name]'] = 'Shipping';
            $payload[$prefix.'[quantity]'] = 1;
        }
        $response = Http::withBasicAuth($secret, '')->asForm()->timeout(20)->post('https://api.stripe.com/v1/checkout/sessions', $payload);
        if (!$response->successful() || !is_string($response->json('url'))) {
            throw new RuntimeException('The payment provider could not start checkout. Check the Stripe account and configuration.');
        }
        return ['id' => (string) $response->json('id'), 'url' => (string) $response->json('url')];
    }

    public function minorUnits(float $amount, string $currency): int
    {
        return in_array(strtoupper($currency), ['JPY'], true) ? (int) round($amount) : (int) round($amount * 100);
    }
}
