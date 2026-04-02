<?php

namespace Database\Seeders;

use App\Models\Area;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::areas() as $index => $area) {
            Area::query()->updateOrCreate(
                [
                    'name' => $area['name'],
                    'city' => $area['city'],
                ],
                $area + ['created_at' => now()->subDays(60 - ($index * 4))]
            );
        }
    }
}
