<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class VehiclesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::vehicles() as $vehicle) {
            Vehicle::query()->updateOrCreate(
                ['plate_number' => $vehicle['plate_number']],
                $vehicle
            );
        }
    }
}
