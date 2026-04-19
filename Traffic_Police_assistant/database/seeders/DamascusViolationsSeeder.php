<?php

namespace Database\Seeders;

use App\Models\Area;
use Database\Seeders\Support\GeographicViolationGenerator;
use Illuminate\Database\Seeder;

class DamascusViolationsSeeder extends Seeder
{
    public function run(): void
    {
        $damascusAreasCount = Area::query()
            ->where('city', 'Damascus')
            ->whereNotNull('center_lat')
            ->whereNotNull('center_lng')
            ->count();

        if ($damascusAreasCount === 0) {
            return;
        }

        GeographicViolationGenerator::seed(
            count: $damascusAreasCount * 12,
            seed: 20260414,
            city: 'Damascus',
        );
    }
}
