<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Sign in securely');
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
