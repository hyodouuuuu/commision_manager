<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Paket Old',
            'Chibi Basic',
            'Chibi Detail',
            'Normal Standar',
            'Normal Premium',
            'Make to Use',
            'Lainnya',
        ] as $name) {
            Package::query()->firstOrCreate(
                ['name' => $name],
                [
                    'default_price' => '0.00',
                    'description' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
