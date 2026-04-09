<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            CitiesSeeder::class,
            AreasSeeder::class,
            ViolationTypesSeeder::class,
            VehiclesSeeder::class,
            ReportLocationsSeeder::class,
            CitizenReportsSeeder::class,
            ViolationsSeeder::class,
            AppealsSeeder::class,
            OfficerLiveLocationsSeeder::class,
            HeatmapAnalysisCacheSeeder::class,
        ]);
    }
}
