<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderAddon;
use App\Models\Package;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_seeder_creates_placeholder_packages_without_overwriting_custom_prices(): void
    {
        $this->seed(PackageSeeder::class);

        $expectedNames = [
            'Paket Old',
            'Chibi Basic',
            'Chibi Detail',
            'Normal Standar',
            'Normal Premium',
            'Make to Use',
            'Lainnya',
        ];

        $this->assertSame($expectedNames, Package::query()->orderBy('id')->pluck('name')->all());
        $this->assertSame(
            7,
            Package::query()->where('default_price', '0.00')->count(),
        );

        Package::query()->where('name', 'Chibi Basic')->update(['default_price' => '125.00']);
        $this->seed(PackageSeeder::class);

        $this->assertSame(
            '125.00',
            Package::query()->where('name', 'Chibi Basic')->firstOrFail()->default_price,
        );
        $this->assertSame(7, Package::query()->count());
    }

    public function test_order_snapshots_package_and_keeps_queue_numbers_after_deletion(): void
    {
        $customer = Customer::factory()->create(['username' => 'reused-name']);
        Customer::factory()->create(['username' => 'reused-name']);
        $package = Package::factory()->create([
            'name' => 'Chibi Basic',
            'default_price' => '125.50',
        ]);

        $deletedOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'package_name' => null,
            'package_price' => null,
        ]);
        $deletedQueueNumber = $deletedOrder->queue_number;

        $this->assertSame('Chibi Basic', $deletedOrder->package_name);
        $this->assertSame('125.50', $deletedOrder->package_price);
        $this->assertSame('125.50', $deletedOrder->total_price);
        $this->assertSame($customer->id, $deletedOrder->customer->id);
        $this->assertSame($package->id, $deletedOrder->package->id);
        $this->assertTrue($customer->orders->contains($deletedOrder));
        $this->assertTrue($package->orders->contains($deletedOrder));

        $deletedOrder->delete();

        $nextOrder = Order::factory()->create();

        $this->assertGreaterThan($deletedQueueNumber, $nextOrder->queue_number);
    }

    public function test_addon_and_payment_totals_stay_accurate_when_records_change(): void
    {
        $order = Order::factory()->create([
            'package_name' => 'Normal Standar',
            'package_price' => '100.00',
        ]);

        $firstAddon = $order->orderAddons()->create([
            'name' => 'Extra expression',
            'price' => '10.10',
        ]);
        $secondAddon = $order->orderAddons()->create([
            'name' => 'Extra outfit',
            'price' => '5.25',
        ]);

        $order->refresh();
        $this->assertCount(2, OrderAddon::query()->where('order_id', $order->queue_number)->get());
        $this->assertSame('15.35', $order->addon_total);
        $this->assertSame('115.35', $order->total_price);
        $this->assertSame($order->queue_number, $firstAddon->order->queue_number);
        $this->assertTrue($order->orderAddons->contains($secondAddon));

        $firstAddon->update(['price' => '20.10']);

        $this->assertSame('25.35', $order->fresh()->addon_total);
        $this->assertSame('125.35', $order->fresh()->total_price);

        $secondAddon->delete();
        $order->refresh();

        $this->assertSame('20.10', $order->addon_total);
        $this->assertSame('120.10', $order->total_price);
        $this->assertSame('0.00', $order->total_payment);
        $this->assertSame('120.10', $order->remaining_payment);
        $this->assertSame('Belum Bayar', $order->payment_status);

        $firstPayment = $order->payments()->create([
            'amount' => '60.05',
            'payment_date' => '2026-11-05',
        ]);
        $this->assertSame('DP', $order->payment_status);
        $this->assertSame('60.05', $order->total_payment);
        $this->assertSame('60.05', $order->remaining_payment);
        $this->assertSame($order->queue_number, $firstPayment->order->queue_number);

        $order->load('payments');
        $order->payments()->create([
            'amount' => '60.05',
            'payment_date' => '2026-11-06',
        ]);
        $this->assertSame('Lunas', $order->payment_status);
        $this->assertSame('120.10', $order->total_payment);
        $this->assertSame('0.00', $order->remaining_payment);

        $order->payments()->create([
            'amount' => '10.00',
            'payment_date' => '2026-11-07',
        ]);
        $this->assertSame('130.10', $order->total_payment);
        $this->assertSame('Lunas', $order->payment_status);
        $this->assertCount(3, $order->payments()->get());
    }

    public function test_order_save_uses_current_addons_even_if_its_model_instance_is_stale(): void
    {
        $order = Order::factory()->create([
            'package_price' => '100.00',
        ]);

        $order->orderAddons()->create([
            'name' => 'Extra',
            'price' => '5.00',
        ]);

        $order->update(['package_price' => '110.00']);

        $this->assertSame('5.00', $order->fresh()->addon_total);
        $this->assertSame('115.00', $order->fresh()->total_price);
    }

    public function test_negative_payments_are_rejected_without_supporting_refunds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amounts cannot be negative.');

        Order::factory()->create()->payments()->create([
            'amount' => '-1.00',
            'payment_date' => '2026-11-05',
        ]);
    }
}
