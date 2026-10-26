<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OrderPaymentService
{
    public function __construct(private readonly OrderEmailService $emails) {}

    public function markPaid(Order $order, array $session, StripeCheckoutService $stripe): Order
    {
        if (($session['payment_status'] ?? null) !== 'paid') return $order;
        if ((string) ($session['metadata']['order_id'] ?? '') !== (string) $order->id) throw new RuntimeException('Payment order reference did not match.');
        if ($order->payment_reference && (string) ($session['id'] ?? '') !== $order->payment_reference) throw new RuntimeException('Payment session reference did not match.');
        if (strtoupper((string) ($session['currency'] ?? '')) !== strtoupper($order->currency)) throw new RuntimeException('Payment currency did not match the order.');
        if ((int) ($session['amount_total'] ?? -1) !== $stripe->minorUnits((float) $order->grand_total, $order->currency)) throw new RuntimeException('Payment amount did not match the order total.');

        [$paidOrder, $newlyPaid] = DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->payment_status === 'paid') return [$lockedOrder, false];

            $items = $lockedOrder->items()->get();
            $lockedProducts = [];
            $manualReview = false;
            foreach ($items as $item) {
                if (!$item->product_id) { $manualReview = true; continue; }
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                if (!$product) { $manualReview = true; continue; }
                $variantId = (int) ($item->attributes['variant_id'] ?? 0);
                if ($variantId > 0) {
                    $variant = ProductVariant::whereKey($variantId)->where('product_id', $product->id)->lockForUpdate()->first();
                    if (!$variant || $variant->stock < $item->quantity) { $manualReview = true; continue; }
                    $lockedProducts[] = ['record' => $variant, 'parent' => $product, 'quantity' => $item->quantity];
                } else {
                    if ($product->stock < $item->quantity) { $manualReview = true; continue; }
                    $lockedProducts[] = ['record' => $product, 'quantity' => $item->quantity];
                }
            }

            if (!$manualReview) {
                foreach ($lockedProducts as $line) {
                    $line['record']->decrement('stock', $line['quantity']);
                    if (isset($line['parent'])) $line['parent']->decrement('stock', $line['quantity']);
                }
            }
            $lockedOrder->update([
                'payment_status' => 'paid',
                'order_status' => $manualReview ? 'manual_review' : 'processing',
                'paid_at' => now(),
            ]);
            return [$lockedOrder->fresh(), true];
        });

        if ($newlyPaid) {
            try { $this->emails->paymentConfirmed($paidOrder); }
            catch (Throwable $exception) { report($exception); }
        }
        return $paidOrder;
    }
}
