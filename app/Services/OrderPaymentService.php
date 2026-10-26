<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPaymentService
{
    public function markPaid(Order $order, array $session, StripeCheckoutService $stripe): Order
    {
        if (($session['payment_status'] ?? null) !== 'paid') return $order;
        if ((string) ($session['metadata']['order_id'] ?? '') !== (string) $order->id) throw new RuntimeException('Payment order reference did not match.');
        if ($order->payment_reference && (string) ($session['id'] ?? '') !== $order->payment_reference) throw new RuntimeException('Payment session reference did not match.');
        if (strtoupper((string) ($session['currency'] ?? '')) !== strtoupper($order->currency)) throw new RuntimeException('Payment currency did not match the order.');
        if ((int) ($session['amount_total'] ?? -1) !== $stripe->minorUnits((float) $order->grand_total, $order->currency)) throw new RuntimeException('Payment amount did not match the order total.');

        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->payment_status === 'paid') return $lockedOrder;

            $items = $lockedOrder->items()->get();
            $lockedProducts = [];
            $manualReview = false;
            foreach ($items as $item) {
                if (!$item->product_id) { $manualReview = true; continue; }
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                if (!$product || $product->stock < $item->quantity) { $manualReview = true; continue; }
                $lockedProducts[] = ['product' => $product, 'quantity' => $item->quantity];
            }

            if (!$manualReview) {
                foreach ($lockedProducts as $line) {
                    $line['product']->decrement('stock', $line['quantity']);
                }
            }

            $lockedOrder->update([
                'payment_status' => 'paid',
                'order_status' => $manualReview ? 'manual_review' : 'processing',
                'paid_at' => now(),
            ]);
            return $lockedOrder->fresh();
        });
    }
}
