<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAddonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_addons_can_be_added_updated_and_deleted_with_totals_recalculated(): void
    {
        $order = Order::factory()->create(['package_price' => '100.00']);

        $response = $this->postJson('/api/orders/'.$order->queue_number.'/addons', [
            'addons' => [
                ['name' => 'Extra expression', 'price' => '10.10'],
                ['name' => 'Extra outfit', 'price' => '5.25'],
            ],
        ]);

        $response->assertCreated()->assertJsonCount(2, 'data');
        $this->assertDatabaseHas('orders', [
            'queue_number' => $order->queue_number,
            'addon_total' => '15.35',
            'total_price' => '115.35',
        ]);

        $addonId = $response->json('data.0.id');
        $this->getJson('/api/orders/'.$order->queue_number.'/addons')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->patchJson('/api/orders/'.$order->queue_number.'/addons/'.$addonId, [
            'price' => '20.00',
        ])->assertOk()->assertJsonPath('data.price', '20.00');

        $this->assertDatabaseHas('orders', [
            'queue_number' => $order->queue_number,
            'addon_total' => '25.25',
            'total_price' => '125.25',
        ]);

        $this->deleteJson('/api/orders/'.$order->queue_number.'/addons/'.$addonId)->assertNoContent();

        $this->assertDatabaseHas('orders', [
            'queue_number' => $order->queue_number,
            'addon_total' => '5.25',
            'total_price' => '105.25',
        ]);
    }

    public function test_addon_amount_is_validated_and_addon_must_belong_to_the_order(): void
    {
        $order = Order::factory()->create();
        $otherOrder = Order::factory()->create();
        $addon = $otherOrder->orderAddons()->create(['name' => 'Other add-on', 'price' => '1.00']);

        $this->postJson('/api/orders/'.$order->queue_number.'/addons', [
            'name' => 'Invalid add-on',
            'price' => '-1.00',
        ])->assertUnprocessable()->assertJsonValidationErrors(['price']);

        $this->putJson('/api/orders/'.$order->queue_number.'/addons/'.$addon->id, [
            'name' => 'Changed',
        ])->assertNotFound();
    }
}
