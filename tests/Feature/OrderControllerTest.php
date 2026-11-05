<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_chooses_or_creates_customer_and_snapshots_package_price_and_queue_number(): void
    {
        $customer = Customer::factory()->create();
        $package = Package::factory()->create([
            'name' => 'Chibi Basic',
            'default_price' => '100.00',
        ]);

        $firstOrder = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'order_date' => '2026-11-05',
            'deadline' => '2026-12-01',
            'queue_number' => 99999,
        ]);

        $firstOrder->assertCreated()
            ->assertJsonPath('data.queue_number', 1)
            ->assertJsonPath('data.package_name', 'Chibi Basic')
            ->assertJsonPath('data.package_price', '100.00')
            ->assertJsonPath('data.total_price', '100.00');

        $package->update(['name' => 'Renamed Package', 'default_price' => '250.00']);

        $secondOrder = $this->postJson('/api/orders', [
            'customer' => [
                'account_name' => 'New customer',
                'username' => 'duplicate-is-ok',
                'platform' => 'Discord',
            ],
            'package_id' => $package->id,
            'package_price' => '200.25',
            'order_date' => '2026-11-06',
        ]);

        $secondOrder->assertCreated()
            ->assertJsonPath('data.queue_number', 2)
            ->assertJsonPath('data.package_name', 'Renamed Package')
            ->assertJsonPath('data.package_price', '200.25');

        $this->assertDatabaseHas('orders', [
            'queue_number' => 1,
            'package_name' => 'Chibi Basic',
            'package_price' => '100.00',
        ]);
        $this->assertDatabaseCount('customers', 2);
    }

    public function test_order_can_be_updated_searched_sorted_and_grouped_by_month(): void
    {
        $customer = Customer::factory()->create([
            'account_name' => 'Moonlight Artist',
            'username' => 'moonlight',
        ]);
        $firstOrder = Order::factory()->for($customer)->create([
            'order_date' => '2026-02-02',
            'deadline' => '2026-12-31',
        ]);
        $secondOrder = Order::factory()->create([
            'order_date' => '2026-03-05',
            'work_status' => 'on_progress',
        ]);

        $this->patchJson('/api/orders/'.$firstOrder->queue_number, [
            'actual_start_date' => '2026-02-10',
            'work_status' => 'selesai',
            'order_status' => 'cancelled',
            'queue_number' => 99999,
        ])->assertOk()
            ->assertJsonPath('data.actual_start_date', '2026-02-10')
            ->assertJsonPath('data.work_status', 'selesai')
            ->assertJsonPath('data.order_status', 'active')
            ->assertJsonPath('data.queue_number', $firstOrder->queue_number);

        $this->patchJson('/api/orders/'.$firstOrder->queue_number, ['package_id' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['package_name']);

        $this->getJson('/api/orders?search=Moonlight')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $firstOrder->queue_number);

        $this->getJson('/api/orders/'.$firstOrder->queue_number)
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.total_payment', '0.00');

        $this->getJson('/api/orders?search=moonlight')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $firstOrder->queue_number);

        $this->getJson('/api/orders?search='.$secondOrder->queue_number)
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $secondOrder->queue_number);

        $this->getJson('/api/orders?filter=on_progress&sort=queue_desc')
            ->assertOk()
            ->assertJsonPath('data.data.0.queue_number', $secondOrder->queue_number);

        $this->getJson('/api/orders/monthly?year=2026')
            ->assertOk()
            ->assertJsonPath('data.2026-02.0.queue_number', $firstOrder->queue_number)
            ->assertJsonPath('data.2026-03.0.queue_number', $secondOrder->queue_number);
    }

    public function test_cancelled_order_retains_payments_but_is_excluded_from_active_estimate_and_filters(): void
    {
        $this->travelTo('2026-11-05 12:00:00');

        $order = Order::factory()->create([
            'package_price' => '120.00',
            'deadline' => '2026-11-04',
        ]);
        $payment = Payment::factory()->for($order)->create(['amount' => '25.00']);
        Order::factory()->create(['package_price' => '80.00']);

        $this->postJson('/api/orders/'.$order->queue_number.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.order_status', 'cancelled')
            ->assertJsonPath('data.total_payment', '25.00');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->queue_number,
            'amount' => '25.00',
        ]);

        $this->getJson('/api/orders?filter=dibatalkan')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('active_estimate_total', '80.00');

        $this->getJson('/api/orders?filter=deadline')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');

        $this->getJson('/api/orders?filter=belum_bayar')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    public function test_payment_and_work_status_filters_select_the_expected_orders(): void
    {
        $unpaid = Order::factory()->create([
            'work_status' => 'belum_dikerjakan',
            'deadline' => null,
        ]);
        $partial = Order::factory()->create([
            'work_status' => 'on_progress',
            'package_price' => '100.00',
            'deadline' => '2026-12-01',
        ]);
        $paid = Order::factory()->create([
            'work_status' => 'selesai',
            'package_price' => '100.00',
        ]);
        $partial->payments()->create(['amount' => '25.00', 'payment_date' => '2026-11-05']);
        $paid->payments()->create(['amount' => '100.00', 'payment_date' => '2026-11-05']);
        $this->travelTo('2026-11-05 12:00:00');

        $this->getJson('/api/orders?filter=belum_bayar')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $unpaid->queue_number);

        $this->getJson('/api/orders?filter=dp')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $partial->queue_number);

        $this->getJson('/api/orders?filter=lunas')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $paid->queue_number);

        $this->getJson('/api/orders?filter=belum_dikerjakan')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $unpaid->queue_number);

        $this->getJson('/api/orders?filter=selesai')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $paid->queue_number);

        $this->getJson('/api/orders?filter=deadline')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.queue_number', $partial->queue_number);
    }

    public function test_permanent_order_deletion_removes_associated_addons_and_payments(): void
    {
        $order = Order::factory()->create();
        $addon = $order->orderAddons()->create(['name' => 'Extra', 'price' => '5.00']);
        $payment = $order->payments()->create([
            'amount' => '5.00',
            'payment_date' => '2026-11-05',
        ]);

        $this->deleteJson('/api/orders/'.$order->queue_number)->assertNoContent();

        $this->assertDatabaseMissing('orders', ['queue_number' => $order->queue_number]);
        $this->assertDatabaseMissing('order_addons', ['id' => $addon->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);

        $newOrder = Order::factory()->create();
        $this->assertGreaterThan($order->queue_number, $newOrder->queue_number);
    }
}
