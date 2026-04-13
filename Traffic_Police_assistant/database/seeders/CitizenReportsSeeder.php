<?php

namespace Database\Seeders;

use App\Models\CitizenReport;
use App\Models\ReportAssignment;
use App\Models\ReportLocation;
use App\Models\User;
use Database\Seeders\Support\TrafficSeedData;
use Illuminate\Database\Seeder;

class CitizenReportsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TrafficSeedData::citizenReports() as $reportData) {
            $location = ReportLocation::query()
                ->where('landmark', $reportData['report_location_landmark'])
                ->firstOrFail();

            $officer = User::query()
                ->where('email', $reportData['assigned_officer_email'])
                ->firstOrFail();

            $submittedAt = now()
                ->subDays($reportData['submitted_days_ago'])
                ->subHours($reportData['submitted_hours_ago']);

            $acceptedAt = $reportData['accepted_delay_minutes'] !== null
                ? $submittedAt->copy()->addMinutes($reportData['accepted_delay_minutes'])
                : null;

            $closedAt = $reportData['close_delay_minutes'] !== null
                ? $submittedAt->copy()->addMinutes($reportData['close_delay_minutes'])
                : null;

            $report = CitizenReport::query()->updateOrCreate(
                ['title' => $reportData['title'], 'submitted_at' => $submittedAt],
                [
                    'reporter_name' => $reportData['reporter_name'],
                    'reporter_phone' => $reportData['reporter_phone'],
                    'reporter_email' => $reportData['reporter_email'],
                    'report_location_id' => $location->id,
                    'description' => $reportData['description'],
                    'image_path' => $reportData['image_path'],
                    'status' => $reportData['status'],
                    'priority' => $reportData['priority'],
                    'created_at' => $submittedAt,
                    'assigned_officer_id' => $officer->id,
                    'accepted_at' => $acceptedAt,
                    'closed_at' => $closedAt,
                    'dispatch_attempts_count' => $reportData['dispatch_attempts_count'],
                    'last_dispatch_at' => $submittedAt->copy()->addMinutes(5),
                ]
            );

            ReportAssignment::query()->updateOrCreate(
                [
                    'citizen_report_id' => $report->id,
                    'officer_id' => $officer->id,
                ],
                [
                    'assignment_order' => 1,
                    'distance_km' => $reportData['distance_km'],
                    'assignment_status' => $reportData['assignment_status'],
                    'assigned_at' => $submittedAt->copy()->addMinutes(5),
                    'responded_at' => $acceptedAt,
                    'response_deadline' => $submittedAt->copy()->addMinutes(25),
                    'notes' => $reportData['notes'],
                ]
            );
        }
    }
}
