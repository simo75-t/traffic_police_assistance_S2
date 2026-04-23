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
            // CitizenReportsSeeder::class, // removed because seeded report data was not aligned with real system logic
            ViolationsSeeder::class,
            DamascusViolationsSeeder::class,
            DamascusAreaCleanupSeeder::class,
            AppealsSeeder::class,
            OfficerLiveLocationsSeeder::class,
            HeatmapAnalysisCacheSeeder::class,
        ]);
    }
}
