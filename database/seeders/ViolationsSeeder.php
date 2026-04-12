<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Attachment;
use App\Models\CitizenReport;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationLocation;
use App\Models\ViolationType;
use Database\Seeders\Support\GeographicViolationGenerator;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class ViolationsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::violations() as $index => $violationData) {
            $vehicle = Vehicle::query()->where('plate_number', $violationData['plate_number'])->firstOrFail();
            $violationType = ViolationType::query()->where('name', $violationData['violation_type_name'])->firstOrFail();
            $reporter = User::query()->where('email', $violationData['reported_by_email'])->firstOrFail();
            $location = ViolationLocation::query()->where('landmark', $violationData['location_landmark'])->firstOrFail();

            $sourceReport = $violationData['source_report_title']
                ? CitizenReport::query()->where('title', $violationData['source_report_title'])->first()
                : null;

            $occurredAt = now()
                ->subDays($violationData['occurred_days_ago'])
                ->subHours($violationData['occurred_hours_ago']);

            $violation = Violation::query()->updateOrCreate(
                [
                    'plate_snapshot' => $violationData['plate_snapshot'],
                    'occurred_at' => $occurredAt,
                ],
                [
                    'vehicle_id' => $vehicle->id,
                    'violation_type_id' => $violationType->id,
                    'violation_location_id' => $location->id,
                    'reported_by' => $reporter->id,
                    'source_report_id' => $sourceReport?->id,
                    'description' => $violationData['description'],
                    'fine_amount' => $violationType->fine_amount,
                    'vehicle_snapshot' => json_encode(['plate_number' => $violationData['plate_number']]),
                    'owner_snapshot' => $violationData['owner_snapshot'],
                    'created_at' => $occurredAt,
                    'data_source' => $violationData['data_source'],
                    'is_synthetic' => $violationData['is_synthetic'],
                    'severity_level' => $violationData['severity_level'],
                    'status' => $violationData['status'],
                ]
            );

            Attachment::query()->updateOrCreate(
                [
                    'violation_id' => $violation->id,
                    'file_path' => 'attachments/evidence-' . ($index + 1) . '.jpg',
                ],
                [
                    'file_type' => 'image/jpeg',
                    'uploaded_by' => $reporter->id,
                    'recorded_at' => $occurredAt,
                ]
            );
        }

        $this->seedHotspotBursts();
        GeographicViolationGenerator::seed(500);
    }

    private function seedHotspotBursts(): void
    {
        $vehicles = Vehicle::query()->pluck('id', 'plate_number');
        $reporters = User::query()->pluck('id', 'email');
        $types = ViolationType::query()->pluck('id', 'name');
        $locations = ViolationLocation::query()->get()->keyBy('landmark');

        $bursts = [
            [
                'landmark' => 'Traffic light B2',
                'reporter' => 'officer.samer@traffic.local',
                'types' => ['ط§ظ„ط³ظٹط± ط¨ط¹ظƒط³ ط§ظ„ط§طھط¬ط§ظ‡', 'ظ‚ط·ط¹ ط§ظ„ط¥ط´ط§ط±ط© ط§ظ„ط­ظ…ط±ط§ط،', 'طھط¬ط§ظˆط² ط§ظ„ط³ط±ط¹ط© ط§ظ„ظ…ط­ط¯ط¯ط©'],
                'days_ago' => [0, 1, 2, 3, 5],
                'count' => 16,
                'severity' => 'critical',
                'source' => 'camera',
            ],
            [
                'landmark' => 'Emergency lane section',
                'reporter' => 'officer.lina@traffic.local',
                'types' => ['ط§ظ„ظ‚ظٹط§ط¯ط© ظپظٹ ظ…ظ†ط§ط·ظ‚ ظ…ظ…ظ†ظˆط¹ط©', 'طھط¬ط§ظˆط² ط§ظ„ط³ط±ط¹ط© ط¨ظ†ط³ط¨ط© ظƒط¨ظٹط±ط©', 'طھط¬ط§ظˆط² ط§ظ„ط³ط±ط¹ط© ط§ظ„ظ…ط­ط¯ط¯ط©'],
                'days_ago' => [0, 1, 2, 4],
                'count' => 14,
                'severity' => 'high',
                'source' => 'patrol',
            ],
            [
                'landmark' => 'Near the bakery',
                'reporter' => 'officer.yousef@traffic.local',
                'types' => ['ط§ط³طھط®ط¯ط§ظ… ط§ظ„ظ‡ط§طھظپ ط£ط«ظ†ط§ط، ط§ظ„ظ‚ظٹط§ط¯ط©', 'ظ‚ط·ط¹ ط§ظ„ط¥ط´ط§ط±ط© ط§ظ„ط­ظ…ط±ط§ط،', 'ط¹ط¯ظ… ط¥ط¹ط·ط§ط، ط£ظˆظ„ظˆظٹط© ظ„ظ„ظ…ط´ط§ط©'],
                'days_ago' => [0, 1, 2, 3, 6],
                'count' => 12,
                'severity' => 'medium',
                'source' => 'citizen_report',
            ],
            [
                'landmark' => 'Faculty entrance',
                'reporter' => 'officer.maha@traffic.local',
                'types' => ['ط¹ط¯ظ… طھط´ط؛ظٹظ„ ط§ظ„ط£ط¶ظˆط§ط، ظ„ظٹظ„ط§ظ‹', 'طھط­ظ…ظٹظ„ ط±ظƒط§ط¨ ط£ظˆ ط­ظ…ظˆظ„ط© ط²ط§ط¦ط¯ط©', 'ط§ظ„ظ‚ظٹط§ط¯ط© ط¨ظ…ط±ظƒط¨ط© ط؛ظٹط± طµط§ظ„ط­ط© ظپظ†ظٹط§ظ‹'],
                'days_ago' => [1, 2, 4, 5, 7],
                'count' => 11,
                'severity' => 'medium',
                'source' => 'patrol',
            ],
            [
                'landmark' => 'Beside the bank entrance',
                'reporter' => 'officer.ahmad@traffic.local',
                'types' => ['ط§ظ„ظˆظ‚ظˆظپ ظپظٹ ظ…ظƒط§ظ† ظ…ظ…ظ†ظˆط¹', 'ط§ظ„ظˆظ‚ظˆظپ ط§ظ„ظ…ط²ط¯ظˆط¬', 'ط¹ط¯ظ… ظˆط¬ظˆط¯ ظ„ظˆط­ط§طھ ظ„ظ„ظ…ط±ظƒط¨ط©'],
                'days_ago' => [0, 1, 3, 5, 6],
                'count' => 10,
                'severity' => 'low',
                'source' => 'citizen_report',
            ],
        ];

        $plateNumbers = array_values($vehicles->keys()->all());

        foreach ($bursts as $burstIndex => $burst) {
            $location = $locations->get($burst['landmark']);
            $reporterId = $reporters->get($burst['reporter']);

            if (!$location || !$reporterId) {
                continue;
            }

            for ($iteration = 0; $iteration < $burst['count']; $iteration++) {
                $plateNumber = $plateNumbers[($burstIndex + $iteration) % count($plateNumbers)];
                $vehicleId = $vehicles->get($plateNumber);
                $typeName = $burst['types'][$iteration % count($burst['types'])];
                $typeId = $types->get($typeName);

                if (!$vehicleId || !$typeId) {
                    continue;
                }

                $daysAgo = $burst['days_ago'][$iteration % count($burst['days_ago'])];
                $hoursAgo = 1 + (($iteration * 3) % 22);
                $occurredAt = now()->copy()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes(($iteration * 11) % 60);
                $plateSnapshot = 'plates/hotspot-' . ($burstIndex + 1) . '-' . ($iteration + 1) . '.jpg';

                $violation = Violation::query()->updateOrCreate(
                    ['plate_snapshot' => $plateSnapshot],
                    [
                        'vehicle_id' => $vehicleId,
                        'violation_type_id' => $typeId,
                        'violation_location_id' => $location->id,
                        'reported_by' => $reporterId,
                        'source_report_id' => null,
                        'description' => 'Seeded hotspot violation generated to strengthen heatmap density output around repeated traffic incidents.',
                        'fine_amount' => ViolationType::query()->find($typeId)?->fine_amount,
                        'vehicle_snapshot' => json_encode(['plate_number' => $plateNumber]),
                        'owner_snapshot' => 'owners/hotspot-seeded-' . ($burstIndex + 1) . '-' . ($iteration + 1) . '.jpg',
                        'occurred_at' => $occurredAt,
                        'created_at' => $occurredAt,
                        'data_source' => $burst['source'],
                        'is_synthetic' => false,
                        'severity_level' => $burst['severity'],
                        'status' => 'issued',
                    ]
                );

                Attachment::query()->updateOrCreate(
                    [
                        'violation_id' => $violation->id,
                        'file_path' => 'attachments/hotspot-evidence-' . ($burstIndex + 1) . '-' . ($iteration + 1) . '.jpg',
                    ],
                    [
                        'file_type' => 'image/jpeg',
                        'uploaded_by' => $reporterId,
                        'recorded_at' => $occurredAt,
                    ]
                );
            }
        }
    }

}
