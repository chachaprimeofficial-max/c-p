<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeCheckoutService
{
    private const SUPPORTED_CURRENCIES = ['AED', 'AUD', 'CAD', 'CHF', 'CNY', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'NOK', 'NZD', 'SEK', 'SGD', 'USD'];

    public function createSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') throw new RuntimeException('Card payments are not configured. Add STRIPE_SECRET_KEY to the server environment.');
        $currency = strtolower($order->currency);
        if (!in_array(strtoupper($currency), self::SUPPORTED_CURRENCIES, true)) {
            throw new RuntimeException('This currency is available for display, but the current card gateway does not support charging in it. Choose an available payment currency.');
        }

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $order->email,
            'client_reference_id' => $order->order_number,
            'metadata[order_id]' => (string) $order->id,
            'payment_intent_data[metadata][order_id]' => (string) $order->id,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => $this->minorUnits((float) $order->grand_total, strtoupper($currency)),
            'line_items[0][price_data][product_data][name]' => 'ChachaPrime order '.$order->order_number,
            'line_items[0][quantity]' => 1,
        ];
        $response = Http::withBasicAuth($secret, '')->asForm()->timeout(20)->post('https://api.stripe.com/v1/checkout/sessions', $payload);
        if (!$response->successful() || !is_string($response->json('url'))) {
            throw new RuntimeException('The payment provider could not start checkout. Check the Stripe account and configuration.');
        }
        return ['id' => (string) $response->json('id'), 'url' => (string) $response->json('url')];
    }

    private function minorUnits(float $amount, string $currency): int
    {
        return in_array($currency, ['JPY'], true) ? (int) round($amount) : (int) round($amount * 100);
    }
}
