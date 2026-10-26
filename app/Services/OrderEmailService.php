<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\Mail;

class OrderEmailService
{
    public function paymentConfirmed(Order $order): void
    {
        $items = $order->items()->get()->map(fn ($item) => $item->product_name.' × '.$item->quantity.' — '.$order->currency.' '.number_format((float) $item->line_total, 2))->implode("\n");
        $body = "Thank you for shopping with ChachaPrime.\n\nOrder: {$order->order_number}\nPayment: Confirmed\nTotal: {$order->currency} ".number_format((float) $order->grand_total, 2)."\n\nItems:\n{$items}\n\nShipping charges and delivery timing are based on the selected shipping template and may require final carrier/customs confirmation. You will receive another update when the order ships.";
        Mail::raw($body, function ($message) use ($order) {
            $message->from('no-reply@chachaprime.com', 'ChachaPrime')
                ->to($order->email)
                ->subject('Payment confirmed — '.$order->order_number);
        });
        $admin = (string) config('mail.marketplace_admin', 'admin@chachaprime.com');
        Mail::raw("A payment has been confirmed.\nOrder: {$order->order_number}\nCustomer: {$order->customer_name} ({$order->email})\nTotal: {$order->currency} ".number_format((float) $order->grand_total, 2), function ($message) use ($admin, $order) {
            $message->from('team@chachaprime.com', 'ChachaPrime Operations')->to($admin)->subject('Paid order needs fulfillment — '.$order->order_number);
        });
    }

    public function shipmentUpdated(Order $order): void
    {
        $tracking = $order->tracking_number ?: 'Tracking details will be shared when available.';
        $courier = $order->courier_name ?: 'Courier to be confirmed';
        Mail::raw("Your ChachaPrime order {$order->order_number} has been marked as shipped.\n\nCourier: {$courier}\nTracking: {$tracking}\n\nFor help, contact support@chachaprime.com.", function ($message) use ($order) {
            $message->from('no-reply@chachaprime.com', 'ChachaPrime')->to($order->email)->subject('Your order has shipped — '.$order->order_number);
        });
    }

    public function returnRequested(OrderReturn $return): void
    {
        $return->loadMissing(['order', 'user']);
        $admin = (string) config('mail.marketplace_admin', 'admin@chachaprime.com');
        Mail::raw("A customer return request needs review.\nOrder: ".$return->order?->order_number."\nCustomer: ".$return->user?->name." (".$return->user?->email.")\nReason: {$return->reason}\nDetails: ".($return->details ?: 'No details supplied'), function ($message) use ($admin, $return) {
            $message->from('team@chachaprime.com', 'ChachaPrime Operations')->to($admin)->subject('Return request received — order '.$return->order?->order_number);
        });
    }
}
