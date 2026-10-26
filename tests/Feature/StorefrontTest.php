<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_without_seed_products(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Discover more.');
        $response->assertSee('Source smarter.');
    }

    public function test_customer_authentication_pages_load(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Sign in securely');
        $this->get('/account/login')->assertOk()->assertSee('Sign in.');
        $this->get('/account/register')->assertOk()->assertSee('Create your account.');
        $this->get('/account/verify-email')->assertOk()->assertSee('Check your inbox.');
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_pages_render_for_an_administrator(): void
    {
        $admin = User::create([
            'name' => 'CI Administrator',
            'email' => 'ci-admin@example.test',
            'password' => Hash::make('Strong-Test-Password-123!'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin);
        foreach ([
            '/admin', '/admin/products', '/admin/products/create', '/admin/categories',
            '/admin/brands', '/admin/orders', '/admin/returns', '/admin/customers',
            '/admin/shipping', '/admin/group-buy', '/admin/localization', '/admin/ai', '/admin/settings',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }
    public function test_product_detail_and_checkout_pages_render(): void
    {
        $product = Product::create([
            'name' => 'Checkout view test item',
            'base_price' => 12,
            'retail_price' => 12,
            'base_currency' => 'USD',
            'stock' => 5,
            'status' => 'published',
        ]);

        ProductMedia::create(['product_id' => $product->id, 'type' => 'image', 'path' => 'products/test/main.jpg', 'alt_text' => 'Main product photo', 'sort_order' => 0]);
        ProductMedia::create(['product_id' => $product->id, 'type' => 'image', 'path' => 'products/test/side.jpg', 'alt_text' => 'Side product photo', 'sort_order' => 1]);
        ProductMedia::create(['product_id' => $product->id, 'type' => 'video', 'path' => 'products/test/demo.mp4', 'sort_order' => 99]);
        ProductMedia::create(['product_id' => $product->id, 'type' => 'blueprint', 'path' => 'products/test/blueprint.pdf', 'sort_order' => 0]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Checkout view test item')
            ->assertSee('Side product photo')
            ->assertSee('Blueprint');

        $customer = User::create([
            'name' => 'Checkout Customer',
            'email' => 'checkout-customer@example.test',
            'password' => Hash::make('Strong-Test-Password-123!'),
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($customer)
            ->withSession(['cart' => [(string) $product->id => 1], 'currency' => 'USD'])
            ->get('/checkout')
            ->assertOk()
            ->assertSee('Delivery information')
            ->assertSee('Continue to secure card payment');
    }

}
