<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderAddon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderAddon>
 */
class OrderAddonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'name' => fake()->words(2, true),
            'price' => '0.00',
        ];
    }
}
