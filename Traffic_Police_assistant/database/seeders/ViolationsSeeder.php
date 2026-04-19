<?php

namespace Database\Seeders;

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
        $this->clearHotspotBursts();

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
                ['plate_snapshot' => $violationData['plate_snapshot']],
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
                    'occurred_at' => $occurredAt,
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

    private function clearHotspotBursts(): void
    {
        $hotspotViolations = Violation::query()
            ->where('plate_snapshot', 'like', 'plates/hotspot-%')
            ->get(['id']);

        $hotspotIds = $hotspotViolations->pluck('id')->all();

        if ($hotspotIds === []) {
            return;
        }

        Attachment::query()
            ->whereIn('violation_id', $hotspotIds)
            ->where('file_path', 'like', 'attachments/hotspot-evidence-%')
            ->delete();

        Violation::query()
            ->whereIn('id', $hotspotIds)
            ->delete();
    }

    private function seedHotspotBursts(): void
    {
        $vehicles = Vehicle::query()->pluck('id', 'plate_number');
        $reporters = User::query()->pluck('id', 'email');
        $types = ViolationType::query()->pluck('id', 'name');
        $locations = ViolationLocation::query()->get()->keyBy('landmark');

        $bursts = [
            [
                'landmark' => 'عند الإشارة الضوئية B2',
                'reporter' => 'officer.samer@traffic.local',
                'types' => ['السير بعكس الاتجاه', 'قطع الإشارة الحمراء', 'تجاوز السرعة المحددة'],
                'days_ago' => [0, 1, 2, 3, 5],
                'count' => 6,
                'severity' => 'critical',
                'source' => 'camera',
            ],
            [
                'landmark' => 'ضمن مسار الطوارئ',
                'reporter' => 'officer.lina@traffic.local',
                'types' => ['القيادة في مناطق ممنوعة', 'تجاوز السرعة بنسبة كبيرة', 'تجاوز السرعة المحددة'],
                'days_ago' => [0, 1, 2, 4],
                'count' => 5,
                'severity' => 'high',
                'source' => 'patrol',
            ],
            [
                'landmark' => 'بالقرب من المخبز',
                'reporter' => 'officer.yousef@traffic.local',
                'types' => ['استخدام الهاتف أثناء القيادة', 'قطع الإشارة الحمراء', 'عدم إعطاء أولوية للمشاة'],
                'days_ago' => [0, 1, 2, 3, 6],
                'count' => 5,
                'severity' => 'medium',
                'source' => 'citizen_report',
            ],
            [
                'landmark' => 'عند مدخل الكلية',
                'reporter' => 'officer.maha@traffic.local',
                'types' => ['عدم تشغيل الأضواء ليلاً', 'تحميل ركاب أو حمولة زائدة', 'القيادة بمركبة غير صالحة فنياً'],
                'days_ago' => [1, 2, 4, 5, 7],
                'count' => 5,
                'severity' => 'medium',
                'source' => 'patrol',
            ],
            [
                'landmark' => 'بجانب مدخل المصرف',
                'reporter' => 'officer.ahmad@traffic.local',
                'types' => ['الوقوف في مكان ممنوع', 'الوقوف المزدوج', 'عدم وجود لوحات للمركبة'],
                'days_ago' => [0, 1, 3, 5, 6],
                'count' => 5,
                'severity' => 'low',
                'source' => 'citizen_report',
            ],
            [
                'landmark' => 'قبل العيادة',
                'reporter' => 'officer.yousef@traffic.local',
                'types' => ['استخدام الهاتف أثناء القيادة', 'تغيير المسار دون إشارة', 'عدم الالتزام بالمسار المحدد'],
                'days_ago' => [0, 2, 4, 6],
                'count' => 4,
                'severity' => 'medium',
                'source' => 'patrol',
            ],
            [
                'landmark' => 'بالقرب من ممر المشاة الضوئي',
                'reporter' => 'officer.ahmad@traffic.local',
                'types' => ['الوقوف المزدوج', 'عدم إعطاء أولوية للمشاة', 'إعاقة حركة السير'],
                'days_ago' => [1, 2, 3, 5],
                'count' => 4,
                'severity' => 'medium',
                'source' => 'citizen_report',
            ],
        ];

        $plateNumbers = array_values($vehicles->keys()->all());

        foreach ($bursts as $burstIndex => $burst) {
            $location = $locations->get($burst['landmark']);
            $reporterId = $reporters->get($burst['reporter']);

            if (! $location || ! $reporterId) {
                continue;
            }

            for ($iteration = 0; $iteration < $burst['count']; $iteration++) {
                $plateNumber = $plateNumbers[($burstIndex + $iteration) % count($plateNumbers)];
                $vehicleId = $vehicles->get($plateNumber);
                $typeName = $burst['types'][$iteration % count($burst['types'])];
                $typeId = $types->get($typeName);

                if (! $vehicleId || ! $typeId) {
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
                        'description' => 'مخالفة مولدة ضمن بيانات الاختبار لزيادة كثافة النقاط في المناطق التي تتكرر فيها الحوادث والمخالفات المرورية.',
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
