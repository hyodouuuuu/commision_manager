<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_renders_placeholder_statistics_and_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('Commission Manager')
            ->assertSee('Pendapatan Real')
            ->assertSee('Estimasi Pendapatan')
            ->assertSee('Total Order')
            ->assertSee('Antrean Aktif')
            ->assertSee('Deadline Terdekat')
            ->assertSee(route('dashboard.orders'), false)
            ->assertSee(route('dashboard.customers'), false)
            ->assertSee(route('dashboard.packages'), false)
            ->assertSee(route('dashboard.finance'), false);
    }

    public function test_sidebar_navigation_opens_the_order_page_and_other_placeholder_pages(): void
    {
        $this->get('/orders')
            ->assertOk()
            ->assertViewIs('orders.index')
            ->assertSee('Daftar Order')
            ->assertSee('data-orders-page', false);

        $this->get('/customers')
            ->assertOk()
            ->assertViewIs('placeholder')
            ->assertSee('Customer');

        $this->get('/packages')
            ->assertOk()
            ->assertViewIs('placeholder')
            ->assertSee('Paket Commission');

        $this->get('/finance')
            ->assertOk()
            ->assertViewIs('placeholder')
            ->assertSee('Keuangan');
    }
}
