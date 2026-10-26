<?php

namespace Tests\Feature;

use App\Models\GroupBuyCampaign;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderEmailService;
use App\Services\OrderPaymentService;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBuyPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_payment_counts_a_buyer_once_and_completes_the_campaign_at_target(): void
    {
        $user = User::create([
            'name' => 'Group Buyer',
            'email' => 'group-buyer@example.test',
            'password' => 'Strong-Test-Password-123!',
            'email_verified_at' => now(),
        ]);
        $product = Product::create([
            'name' => 'Group-buy item',
            'base_price' => 10,
            'retail_price' => 10,
            'base_currency' => 'USD',
            'stock' => 5,
            'group_buy_enabled' => true,
            'status' => 'published',
        ]);
        $campaign = GroupBuyCampaign::create([
            'product_id' => $product->id,
            'title' => 'Target test campaign',
            'unit_price' => 10,
            'currency' => 'USD',
            'target_participants' => 2,
            'current_participants' => 1,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'email' => $user->email,
            'currency' => 'USD',
            'subtotal' => 10,
            'shipping_total' => 0,
            'grand_total' => 10,
            'payment_provider' => 'stripe',
            'payment_status' => 'pending',
            'order_status' => 'pending_payment',
            'payment_reference' => 'cs_test_group_buy',
            'shipping_country' => 'US',
            'shipping_city' => 'Los Angeles',
            'shipping_address' => 'Test address',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
            'currency' => 'USD',
            'attributes' => ['group_buy_campaign_id' => $campaign->id],
        ]);

        $emails = \Mockery::mock(OrderEmailService::class);
        $emails->shouldReceive('paymentConfirmed')->once();
        $stripe = \Mockery::mock(StripeCheckoutService::class);
        $stripe->shouldReceive('minorUnits')->twice()->with(10.0, 'USD')->andReturn(1000);
        $service = new OrderPaymentService($emails);
        $session = [
            'payment_status' => 'paid',
            'metadata' => ['order_id' => (string) $order->id],
            'id' => 'cs_test_group_buy',
            'currency' => 'usd',
            'amount_total' => 1000,
        ];

        $service->markPaid($order, $session, $stripe);
        $service->markPaid($order->fresh(), $session, $stripe);

        $this->assertSame(2, $campaign->fresh()->current_participants);
        $this->assertSame('completed', $campaign->fresh()->status);
        $this->assertDatabaseCount('group_buy_participants', 1);
        $this->assertSame(4, $product->fresh()->stock);
    }
    public function test_stock_review_payment_does_not_count_as_a_group_buy_participant(): void
    {
        $user = User::create([
            'name' => 'Review Buyer',
            'email' => 'review-buyer@example.test',
            'password' => 'Strong-Test-Password-123!',
            'email_verified_at' => now(),
        ]);
        $product = Product::create([
            'name' => 'Out-of-stock group-buy item',
            'base_price' => 10,
            'retail_price' => 10,
            'base_currency' => 'USD',
            'stock' => 0,
            'group_buy_enabled' => true,
            'status' => 'published',
        ]);
        $campaign = GroupBuyCampaign::create([
            'product_id' => $product->id,
            'title' => 'Manual review campaign',
            'unit_price' => 10,
            'currency' => 'USD',
            'target_participants' => 2,
            'current_participants' => 0,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'email' => $user->email,
            'currency' => 'USD',
            'subtotal' => 10,
            'shipping_total' => 0,
            'grand_total' => 10,
            'payment_provider' => 'stripe',
            'payment_status' => 'pending',
            'order_status' => 'pending_payment',
            'payment_reference' => 'cs_test_stock_review',
            'shipping_country' => 'US',
            'shipping_city' => 'Los Angeles',
            'shipping_address' => 'Test address',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 10,
            'line_total' => 10,
            'currency' => 'USD',
            'attributes' => ['group_buy_campaign_id' => $campaign->id],
        ]);

        $emails = \Mockery::mock(OrderEmailService::class);
        $emails->shouldReceive('paymentConfirmed')->once();
        $stripe = \Mockery::mock(StripeCheckoutService::class);
        $stripe->shouldReceive('minorUnits')->twice()->with(10.0, 'USD')->andReturn(1000);
        $service = new OrderPaymentService($emails);
        $session = [
            'payment_status' => 'paid',
            'metadata' => ['order_id' => (string) $order->id],
            'id' => 'cs_test_stock_review',
            'currency' => 'usd',
            'amount_total' => 1000,
        ];

        $service->markPaid($order, $session, $stripe);

        $this->assertSame('manual_review', $order->fresh()->order_status);
        $this->assertSame(0, $campaign->fresh()->current_participants);
        $this->assertDatabaseCount('group_buy_participants', 0);
    }

}
