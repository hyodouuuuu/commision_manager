<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_payments_change_status_automatically_and_allow_overpayment(): void
    {
        $order = Order::factory()->create(['package_price' => '100.00']);

        $this->getJson('/api/orders/'.$order->queue_number.'/payments')
            ->assertOk()
            ->assertJsonPath('total_payment', '0.00')
            ->assertJsonPath('remaining_payment', '100.00')
            ->assertJsonPath('payment_status', 'Belum Bayar');

        $firstPayment = $this->postJson('/api/orders/'.$order->queue_number.'/payments', [
            'amount' => '40.00',
            'payment_date' => '2026-11-05',
        ]);
        $firstPayment->assertCreated()
            ->assertJsonPath('payment_status', 'DP')
            ->assertJsonPath('remaining_payment', '60.00');

        $secondPayment = $this->postJson('/api/orders/'.$order->queue_number.'/payments', [
            'amount' => '60.00',
            'payment_date' => '2026-11-06',
        ]);
        $secondPayment->assertCreated()
            ->assertJsonPath('payment_status', 'Lunas')
            ->assertJsonPath('remaining_payment', '0.00');

        $this->postJson('/api/orders/'.$order->queue_number.'/payments', [
            'amount' => '25.00',
            'payment_date' => '2026-11-07',
        ])->assertCreated()
            ->assertJsonPath('total_payment', '125.00')
            ->assertJsonPath('payment_status', 'Lunas');

        $this->assertDatabaseCount('payments', 3);
    }

    public function test_payment_edit_delete_and_validation_keep_payment_state_consistent(): void
    {
        $order = Order::factory()->create(['package_price' => '100.00']);

        $invalid = $this->postJson('/api/orders/'.$order->queue_number.'/payments', [
            'amount' => '0.00',
        ]);
        $invalid->assertUnprocessable()->assertJsonValidationErrors(['amount', 'payment_date']);

        $payment = $order->payments()->create([
            'amount' => '75.00',
            'payment_date' => '2026-11-05',
        ]);

        $this->putJson('/api/orders/'.$order->queue_number.'/payments/'.$payment->id, [
            'amount' => '110.00',
            'payment_date' => '2026-11-06',
        ])->assertOk()
            ->assertJsonPath('total_payment', '110.00')
            ->assertJsonPath('payment_status', 'Lunas');

        $this->deleteJson('/api/orders/'.$order->queue_number.'/payments/'.$payment->id)->assertNoContent();

        $this->getJson('/api/orders/'.$order->queue_number.'/payments')
            ->assertOk()
            ->assertJsonPath('total_payment', '0.00')
            ->assertJsonPath('payment_status', 'Belum Bayar');
    }
}
