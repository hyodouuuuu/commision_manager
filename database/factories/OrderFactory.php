<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'package_id' => null,
            'package_name' => 'Lainnya',
            'package_price' => '0.00',
            'order_date' => fake()->date(),
            'agreed_start_date' => null,
            'actual_start_date' => null,
            'deadline' => null,
            'notes' => fake()->optional()->sentence(),
            'work_status' => 'belum_dikerjakan',
            'order_status' => 'active',
        ];
    }
}
