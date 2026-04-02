<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\CitizenReport;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationLocation;
use App\Models\ViolationType;
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
    }
}
