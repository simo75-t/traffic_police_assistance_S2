<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\ReportLocation;
use App\Models\ViolationLocation;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class ReportLocationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::reportLocations() as $index => $location) {
            $area = Area::query()->where('name', $location['area_name'])->firstOrFail();

            ReportLocation::query()->updateOrCreate(
                ['street_name' => $location['street_name'], 'landmark' => $location['landmark']],
                [
                    'area_id' => $area->id,
                    'address' => $location['address'],
                    'street_name' => $location['street_name'],
                    'landmark' => $location['landmark'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'city' => $location['city'],
                    'created_at' => now()->subDays(20 - $index),
                ]
            );
        }

        foreach (TrafficSeedData::violationLocations() as $location) {
            $area = Area::query()->where('name', $location['area_name'])->firstOrFail();

            ViolationLocation::query()->updateOrCreate(
                ['street_name' => $location['street_name'], 'landmark' => $location['landmark']],
                [
                    'area_id' => $area->id,
                    'address' => $location['address'],
                    'street_name' => $location['street_name'],
                    'landmark' => $location['landmark'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'city' => $location['city'],
                ]
            );
        }
    }
}
