<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartPricingService;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_pricing_uses_variant_adjustment_and_stock(): void
    {
        $product = Product::create([
            'name' => 'Test product',
            'base_price' => 10,
            'retail_price' => 10,
            'base_currency' => 'USD',
            'stock' => 5,
            'status' => 'published',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Black / Large',
            'sku' => 'TEST-BLACK-LARGE',
            'stock' => 3,
            'price_adjustment' => 2,
            'attributes' => ['Color' => 'Black', 'Size' => 'Large'],
        ]);

        $lines = app(CartPricingService::class)->lines([
            $product->id.':'.$variant->id => 2,
        ], app(CurrencyService::class));

        $this->assertCount(1, $lines);
        $this->assertSame($product->id.':'.$variant->id, $lines[0]['key']);
        $this->assertSame($variant->id, $lines[0]['variant']->id);
        $this->assertSame(3, $lines[0]['stock']);
        $this->assertEquals(12.00, $lines[0]['unit_price']);
        $this->assertEquals(24.00, $lines[0]['line_total']);
    }

    public function test_cart_key_parser_keeps_legacy_product_keys_compatible(): void
    {
        $this->assertSame([42, 0], app(CartPricingService::class)->parseKey('42'));
        $this->assertSame([42, 7], app(CartPricingService::class)->parseKey('42:7'));
        $this->assertSame([0, 0], app(CartPricingService::class)->parseKey('invalid'));
    }
}
