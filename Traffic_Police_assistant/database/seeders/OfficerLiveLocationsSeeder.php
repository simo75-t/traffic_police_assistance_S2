<?php

namespace Database\Seeders;

use App\Models\OfficerLiveLocation;
use App\Models\User;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class OfficerLiveLocationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::liveLocations() as $index => $locationData) {
            $officer = User::query()->where('email', $locationData['officer_email'])->firstOrFail();

            OfficerLiveLocation::query()->updateOrCreate(
                ['officer_id' => $officer->id],
                [
                    'latitude' => $locationData['latitude'],
                    'longitude' => $locationData['longitude'],
                    'availability_status' => $locationData['availability_status'],
                    'last_update_time' => now()->subMinutes(3 + $index),
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subMinutes(3 + $index),
                ]
            );
        }
    }
}
