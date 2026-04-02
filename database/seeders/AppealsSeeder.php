<?php

namespace Database\Seeders;

use App\Models\Appeal;
use App\Models\Violation;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class AppealsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::appeals() as $appealData) {
            $violation = Violation::query()
                ->where('plate_snapshot', $appealData['plate_snapshot'])
                ->firstOrFail();

            $submittedAt = now()
                ->subDays($appealData['submitted_days_ago'])
                ->subHours($appealData['submitted_hours_ago']);

            $decidedAt = $appealData['decided_days_ago'] !== null && $appealData['decided_hours_ago'] !== null
                ? now()->subDays($appealData['decided_days_ago'])->subHours($appealData['decided_hours_ago'])
                : null;

            Appeal::query()->updateOrCreate(
                ['violation_id' => $violation->id],
                [
                    'status' => $appealData['status'],
                    'reason' => $appealData['reason'],
                    'decision_note' => $appealData['decision_note'],
                    'submitted_at' => $submittedAt,
                    'decided_at' => $decidedAt,
                    'created_at' => $submittedAt,
                    'updated_at' => $decidedAt ?? $submittedAt,
                ]
            );
        }
    }
}
