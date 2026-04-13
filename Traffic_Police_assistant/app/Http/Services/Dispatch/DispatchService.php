<?php

namespace App\Http\Services\Dispatch;

use App\Enums\RoleUserEnum;
use App\Http\Services\Notifications\FcmNotificationService;
use App\Models\CitizenReport;
use App\Models\OfficerLiveLocation;
use App\Models\ReportAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    private const LOCATION_FRESHNESS_SECONDS = 60;
    private const RESPONSE_WINDOW_SECONDS = 60;

    public function __construct(
        private readonly FcmNotificationService $fcmNotificationService
    ) {
    }

    public function dispatchReport(CitizenReport $report, array $excludedOfficerIds = []): ?ReportAssignment
    {
        $assignment = DB::transaction(function () use ($report, $excludedOfficerIds) {
            $lockedReport = CitizenReport::query()
                ->lockForUpdate()
                ->findOrFail($report->id);

            if (in_array($lockedReport->status, ['in_progress', 'closed'], true)) {
                return null;
            }

            $excludedIds = array_values(array_unique(array_filter([
                ...$excludedOfficerIds,
                ...$lockedReport->assignments()
                    ->whereIn('assignment_status', ['rejected', 'expired'])
                    ->pluck('officer_id')
                    ->all(),
            ])));

            $candidate = $this->findNearestAvailableOfficer($lockedReport, $excludedIds);

            if (! $candidate) {
                $lockedReport->update([
                    'status' => 'submitted',
                    'assigned_officer_id' => null,
                ]);

                return null;
            }

            $now = now();
            $assignmentOrder = (int) $lockedReport->assignments()->max('assignment_order') + 1;

            $assignment = ReportAssignment::query()->create([
                'citizen_report_id' => $lockedReport->id,
                'officer_id' => $candidate['officer']->id,
                'assignment_order' => $assignmentOrder,
                'distance_km' => round($candidate['distance_km'], 2),
                'assignment_status' => 'pending',
                'assigned_at' => $now,
                'response_deadline' => $now->copy()->addSeconds(self::RESPONSE_WINDOW_SECONDS),
                'notes' => json_encode([
                    'dispatch_notification' => [
                        'title' => 'New field report assigned',
                        'body' => $lockedReport->title,
                        'report_id' => $lockedReport->id,
                    ],
                ]),
            ]);

            $lockedReport->update([
                'assigned_officer_id' => $candidate['officer']->id,
                'status' => 'dispatched',
                'last_dispatch_at' => $now,
                'dispatch_attempts_count' => (int) $lockedReport->dispatch_attempts_count + 1,
            ]);

            OfficerLiveLocation::query()
                ->where('officer_id', $candidate['officer']->id)
                ->update([
                    'availability_status' => 'responding',
                    'updated_at' => $now,
                ]);

            return $assignment->load(['officer', 'citizenReport.reportLocation']);
        });

        if ($assignment) {
            $this->sendDispatchNotification($assignment);
        }

        return $assignment;
    }

    public function respondToAssignment(
        CitizenReport $report,
        User $officer,
        string $response,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($report, $officer, $response, $notes) {
            $assignment = ReportAssignment::query()
                ->where('citizen_report_id', $report->id)
                ->where('officer_id', $officer->id)
                ->where('assignment_status', 'pending')
                ->latest('assignment_order')
                ->lockForUpdate()
                ->firstOrFail();

            $report = CitizenReport::query()->lockForUpdate()->findOrFail($report->id);
            $now = now();

            if ($response === 'accept') {
                $assignment->update([
                    'assignment_status' => 'accepted',
                    'responded_at' => $now,
                    'notes' => $notes,
                ]);

                $report->update([
                    'status' => 'in_progress',
                    'accepted_at' => $now,
                    'assigned_officer_id' => $officer->id,
                ]);

                OfficerLiveLocation::query()
                    ->where('officer_id', $officer->id)
                    ->update([
                        'availability_status' => 'busy',
                        'updated_at' => $now,
                    ]);

                return [
                    'assignment' => $assignment->fresh(['citizenReport.reportLocation']),
                    'next_assignment' => null,
                ];
            }

            $assignment->update([
                'assignment_status' => 'rejected',
                'responded_at' => $now,
                'notes' => $notes,
            ]);

            OfficerLiveLocation::query()
                ->where('officer_id', $officer->id)
                ->update([
                    'availability_status' => 'available',
                    'updated_at' => $now,
                ]);

            $report->update([
                'assigned_officer_id' => null,
                'status' => 'submitted',
            ]);

            $nextAssignment = $this->dispatchReport($report, [$officer->id]);

            return [
                'assignment' => $assignment->fresh(['citizenReport.reportLocation']),
                'next_assignment' => $nextAssignment,
            ];
        });
    }

    public function expireStaleAssignments(): void
    {
        $expiredAssignments = ReportAssignment::query()
            ->with('citizenReport')
            ->where('assignment_status', 'pending')
            ->whereNotNull('response_deadline')
            ->where('response_deadline', '<', now())
            ->get();

        foreach ($expiredAssignments as $assignment) {
            DB::transaction(function () use ($assignment) {
                $lockedAssignment = ReportAssignment::query()
                    ->lockForUpdate()
                    ->find($assignment->id);

                if (! $lockedAssignment || $lockedAssignment->assignment_status !== 'pending') {
                    return;
                }

                $report = CitizenReport::query()
                    ->lockForUpdate()
                    ->find($lockedAssignment->citizen_report_id);

                if (! $report) {
                    return;
                }

                $lockedAssignment->update([
                    'assignment_status' => 'expired',
                    'responded_at' => now(),
                    'notes' => 'Assignment expired due to no response.',
                ]);

                OfficerLiveLocation::query()
                    ->where('officer_id', $lockedAssignment->officer_id)
                    ->update([
                        'availability_status' => 'available',
                        'updated_at' => now(),
                    ]);

                if ((int) $report->assigned_officer_id === (int) $lockedAssignment->officer_id) {
                    $report->update([
                        'assigned_officer_id' => null,
                        'status' => 'submitted',
                    ]);
                }
            });

            if ($assignment->citizenReport) {
                $this->dispatchReport($assignment->citizenReport, [$assignment->officer_id]);
            }
        }
    }

    private function findNearestAvailableOfficer(CitizenReport $report, array $excludedOfficerIds = []): ?array
    {
        $location = $report->reportLocation;

        if (! $location || $location->latitude === null || $location->longitude === null) {
            return null;
        }

        $candidates = OfficerLiveLocation::query()
            ->select('officers_live_locations.*')
            ->join('users', 'users.id', '=', 'officers_live_locations.officer_id')
            ->where('users.role', RoleUserEnum::Police_officer)
            ->where('users.is_active', true)
            ->where('officers_live_locations.availability_status', 'available')
            ->where('officers_live_locations.last_update_time', '>=', now()->subSeconds(self::LOCATION_FRESHNESS_SECONDS))
            ->when($excludedOfficerIds !== [], fn ($query) => $query->whereNotIn('officers_live_locations.officer_id', $excludedOfficerIds))
            ->lockForUpdate()
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->map(function (OfficerLiveLocation $candidate) use ($location): array {
                return [
                    'officer' => $candidate->officer,
                    'distance_km' => $this->haversineDistanceKm(
                        (float) $location->latitude,
                        (float) $location->longitude,
                        (float) $candidate->latitude,
                        (float) $candidate->longitude
                    ),
                ];
            })
            ->sortBy('distance_km')
            ->first();
    }

    private function haversineDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function sendDispatchNotification(ReportAssignment $assignment): void
    {
        $assignment->loadMissing(['officer', 'citizenReport.reportLocation']);

        $report = $assignment->citizenReport;
        $location = $report?->reportLocation;
        $officer = $assignment->officer;

        if (! $report || ! $officer) {
            return;
        }

        $this->fcmNotificationService->sendToUser(
            $officer,
            'New report assigned',
            $report->title,
            [
                'type' => 'dispatch_assignment',
                'report_id' => $report->id,
                'assignment_id' => $assignment->id,
                'priority' => $report->priority,
                'status' => $report->status,
                'latitude' => $location?->latitude,
                'longitude' => $location?->longitude,
            ]
        );
    }
}
