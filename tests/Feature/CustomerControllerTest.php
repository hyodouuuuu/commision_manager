<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_crud_validates_required_fields_and_allows_duplicate_usernames(): void
    {
        $response = $this->postJson('/api/customers', [
            'account_name' => 'Commissioner One',
            'username' => 'same-handle',
            'platform' => 'X',
            'notes' => 'First customer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.username', 'same-handle');

        $secondResponse = $this->postJson('/api/customers', [
            'account_name' => 'Commissioner Two',
            'username' => 'same-handle',
            'platform' => 'Instagram',
        ]);

        $secondResponse->assertCreated();
        $this->assertDatabaseCount('customers', 2);

        $this->patchJson('/api/customers/'.$response->json('data.id'), [
            'notes' => 'Updated notes',
        ])->assertOk()->assertJsonPath('data.notes', 'Updated notes');

        $this->assertDatabaseHas('customers', [
            'id' => $response->json('data.id'),
            'notes' => 'Updated notes',
        ]);

        $this->postJson('/api/customers', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['account_name', 'username', 'platform']);
    }

    public function test_customer_search_returns_matching_account_and_username_and_detail_includes_order_history(): void
    {
        $customer = Customer::factory()->create([
            'account_name' => 'Starlight Creator',
            'username' => 'star-handle',
        ]);
        Customer::factory()->create([
            'account_name' => 'Other Creator',
            'username' => 'other-handle',
        ]);
        $order = Order::factory()->for($customer)->create();

        $this->getJson('/api/customers?search=star-handle')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $customer->id);

        $this->getJson('/api/customers/'.$customer->id)
            ->assertOk()
            ->assertJsonPath('data.account_name', 'Starlight Creator')
            ->assertJsonPath('orders.data.0.queue_number', $order->queue_number);
    }

    public function test_customer_deletion_succeeds_when_unused_and_returns_conflict_when_orders_exist(): void
    {
        $unusedCustomer = Customer::factory()->create();

        $this->deleteJson('/api/customers/'.$unusedCustomer->id)->assertNoContent();
        $this->assertDatabaseMissing('customers', ['id' => $unusedCustomer->id]);

        $usedCustomer = Customer::factory()->create();
        Order::factory()->for($usedCustomer)->create();

        $this->deleteJson('/api/customers/'.$usedCustomer->id)
            ->assertConflict()
            ->assertJsonPath('message', 'Customer cannot be deleted while orders are attached.');
        $this->assertModelExists($usedCustomer);
    }
}
