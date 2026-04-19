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
        $seedImagePaths = array_column(TrafficSeedData::citizenReports(), 'image_path');

        $seedReportIds = CitizenReport::query()
            ->whereIn('image_path', $seedImagePaths)
            ->pluck('id');

        if ($seedReportIds->isNotEmpty()) {
            ReportAssignment::query()
                ->whereIn('citizen_report_id', $seedReportIds)
                ->delete();

            CitizenReport::query()
                ->whereIn('id', $seedReportIds)
                ->delete();
        }

        foreach (TrafficSeedData::citizenReports() as $reportData) {
            $location = ReportLocation::query()
                ->where('landmark', $reportData['report_location_landmark'])
                ->firstOrFail();

            $officer = ! empty($reportData['assigned_officer_email'])
                ? User::query()->where('email', $reportData['assigned_officer_email'])->firstOrFail()
                : null;

            $submittedAt = now()
                ->subDays($reportData['submitted_days_ago'])
                ->subHours($reportData['submitted_hours_ago']);

            $acceptedAt = $reportData['accepted_delay_minutes'] !== null
                ? $submittedAt->copy()->addMinutes($reportData['accepted_delay_minutes'])
                : null;

            $closedAt = $reportData['close_delay_minutes'] !== null
                ? $submittedAt->copy()->addMinutes($reportData['close_delay_minutes'])
                : null;

            $normalizedStatus = $this->normalizeReportStatus(
                $reportData['status'],
                $officer !== null
            );

            $attributes = [
                'title' => $reportData['title'],
                'reporter_name' => $reportData['reporter_name'],
                'reporter_phone' => $reportData['reporter_phone'],
                'report_location_id' => $location->id,
                'description' => $reportData['description'],
                'image_path' => $reportData['image_path'],
                'status' => $normalizedStatus,
                'priority' => $reportData['priority'],
                'submitted_at' => $submittedAt,
                'created_at' => $submittedAt,
                'assigned_officer_id' => $officer && $normalizedStatus !== 'submitted'
                    ? $officer->id
                    : null,
                'accepted_at' => in_array($normalizedStatus, ['in_progress', 'closed'], true)
                    ? $acceptedAt
                    : null,
                'closed_at' => $closedAt,
                'dispatch_attempts_count' => $reportData['dispatch_attempts_count'],
                'last_dispatch_at' => $officer && $normalizedStatus !== 'submitted'
                    ? $submittedAt->copy()->addMinutes(5)
                    : null,
            ];

            $report = CitizenReport::query()
                ->where('image_path', $reportData['image_path'])
                ->orderBy('id')
                ->first();

            if ($report) {
                $report->fill($attributes);
                $report->save();
            } else {
                $report = CitizenReport::query()->create($attributes);
            }

            $duplicateIds = CitizenReport::query()
                ->where('image_path', $reportData['image_path'])
                ->where('id', '!=', $report->id)
                ->pluck('id');

            if ($duplicateIds->isNotEmpty()) {
                ReportAssignment::query()
                    ->whereIn('citizen_report_id', $duplicateIds)
                    ->delete();

                CitizenReport::query()
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }

            if ($officer) {
                ReportAssignment::query()->updateOrCreate(
                    [
                        'citizen_report_id' => $report->id,
                        'officer_id' => $officer->id,
                    ],
                    [
                        'assignment_order' => 1,
                        'distance_km' => $reportData['distance_km'],
                        'assignment_status' => $normalizedStatus === 'closed' ? 'completed' : 'assigned',
                        'assigned_at' => $submittedAt->copy()->addMinutes(5),
                        'responded_at' => $normalizedStatus === 'closed' ? $closedAt : $acceptedAt,
                        'notes' => $reportData['notes'],
                    ]
                );
            } else {
                ReportAssignment::query()
                    ->where('citizen_report_id', $report->id)
                    ->delete();
            }
        }
    }

    private function normalizeReportStatus(string $status, bool $hasOfficer): string
    {
        if ($status === 'submitted' && $hasOfficer) {
            return 'dispatched';
        }

        return $status;
    }
}
