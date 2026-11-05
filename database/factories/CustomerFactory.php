<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_name' => fake()->name(),
            'username' => fake()->userName(),
            'platform' => fake()->randomElement(['X', 'Instagram', 'Discord', 'YouTube']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
