<?php

namespace Database\Seeders;

use App\Models\HeatmapAnalysisCache;
use App\Models\ViolationType;
use Illuminate\Database\Seeder;

class HeatmapAnalysisCacheSeeder extends Seeder
{
    public function run(): void
    {
        $violationType = ViolationType::query()
            ->where('name', 'الوقوف في مكان ممنوع')
            ->first();

        HeatmapAnalysisCache::query()->updateOrCreate(
            ['cache_key' => 'damascus-weekly-illegal-parking'],
            [
                'violation_type_id' => $violationType?->id ? (string) $violationType->id : null,
                'time_bucket' => 'weekly',
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->toDateString(),
                'grid_size' => 200,
                'generated_at' => now()->subHour(),
                'expires_at' => now()->addHours(23),
                'result_json' => json_encode([
                    'city' => 'Damascus',
                    'hotspots' => [
                        ['lat' => 33.5141020, 'lng' => 36.2762010, 'weight' => 0.84],
                        ['lat' => 33.4864120, 'lng' => 36.2545010, 'weight' => 0.66],
                        ['lat' => 33.5126210, 'lng' => 36.3010810, 'weight' => 0.52],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now()->subHour(),
            ]
        );
    }
}
