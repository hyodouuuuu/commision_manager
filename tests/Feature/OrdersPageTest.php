<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrdersPageTest extends TestCase
{
    public function test_order_page_renders_api_backed_filters_actions_and_order_fields(): void
    {
        $this->get(route('dashboard.orders'))
            ->assertOk()
            ->assertViewIs('orders.index')
            ->assertSee(route('orders.index'), false)
            ->assertSee(route('customers.index'), false)
            ->assertSee(route('packages.index'), false)
            ->assertSee('data-filter-payment', false)
            ->assertSee('data-filter-work', false)
            ->assertSee('data-filter-month', false)
            ->assertSee('data-create-order', false)
            ->assertSee('Tanggal mulai disepakati')
            ->assertSee('Catatan')
            ->assertSee('data-addon-list', false)
            ->assertSee('Hapus permanen');
    }
}
