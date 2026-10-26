<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, OrderPaymentService $payments, StripeCheckoutService $stripe): JsonResponse
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') return response()->json(['message' => 'Webhook is not configured.'], 503);

        $payload = $request->getContent();
        $signatureHeader = (string) $request->header('Stripe-Signature', '');
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) $parts[$pair[0]][] = $pair[1];
        }
        $timestamp = (int) ($parts['t'][0] ?? 0);
        if (!$timestamp || abs(time() - $timestamp) > 300) return response()->json(['message' => 'Invalid webhook timestamp.'], 400);
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $valid = false;
        foreach ($parts['v1'] ?? [] as $signature) if (hash_equals($expected, $signature)) $valid = true;
        if (!$valid) return response()->json(['message' => 'Invalid webhook signature.'], 400);

        $event = json_decode($payload, true);
        if (!is_array($event)) return response()->json(['message' => 'Invalid event payload.'], 400);
        if (!in_array($event['type'] ?? '', ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return response()->json(['received' => true]);
        }

        $session = $event['data']['object'] ?? [];
        $orderId = $session['metadata']['order_id'] ?? null;
        if (!$orderId) return response()->json(['received' => true]);
        $order = Order::find($orderId);
        if (!$order) return response()->json(['received' => true]);
        if (($session['payment_status'] ?? null) !== 'paid') return response()->json(['received' => true]);

        try {
            $payments->markPaid($order, $session, $stripe);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Payment event could not be reconciled.'], 400);
        }
        return response()->json(['received' => true]);
    }
}
