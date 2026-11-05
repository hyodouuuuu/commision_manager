<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_crud_updates_price_and_active_state_and_rejects_negative_prices(): void
    {
        $response = $this->postJson('/api/packages', [
            'name' => 'Chibi Basic',
            'default_price' => '150.00',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.default_price', '150.00');
        $packageId = $response->json('data.id');

        $this->getJson('/api/packages/'.$packageId)
            ->assertOk()
            ->assertJsonPath('data.name', 'Chibi Basic');

        $this->patchJson('/api/packages/'.$packageId, [
            'default_price' => '175.50',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.default_price', '175.50')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('packages', [
            'id' => $packageId,
            'default_price' => '175.50',
            'is_active' => 0,
        ]);

        $this->patchJson('/api/packages/'.$packageId, ['default_price' => '-1.00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['default_price']);
    }

    public function test_package_search_and_deletion_protect_used_packages(): void
    {
        $package = Package::factory()->create(['name' => 'Normal Premium']);
        Package::factory()->create(['name' => 'Chibi Detail']);

        $this->getJson('/api/packages?search=Premium')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $package->id);

        $this->deleteJson('/api/packages/'.$package->id)->assertNoContent();

        $usedPackage = Package::factory()->create();
        Order::factory()->for(Customer::factory())->for($usedPackage)->create();

        $this->deleteJson('/api/packages/'.$usedPackage->id)
            ->assertConflict()
            ->assertJsonPath('message', 'Package cannot be deleted while orders are attached.');
        $this->assertModelExists($usedPackage);
    }
}
