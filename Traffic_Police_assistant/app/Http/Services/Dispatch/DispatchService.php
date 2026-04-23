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
    private const LOCATION_FRESHNESS_SECONDS = 300;
    private const PENDING_RETRY_COOLDOWN_SECONDS = 30;

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

            $excludedIds = array_values(array_unique(array_filter($excludedOfficerIds)));

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
                'assignment_status' => 'assigned',
                'assigned_at' => $now,
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

public function startAssignment(
        ReportAssignment $assignment,
        User $officer,
        ?string $notes = null
    ): ReportAssignment {
        $startedAssignment = DB::transaction(function () use ($assignment, $officer, $notes) {
            $assignment = ReportAssignment::query()
                ->whereKey($assignment->id)
                ->where('officer_id', $officer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $report = CitizenReport::query()
                ->whereKey($assignment->citizen_report_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($report->status === 'closed') {
                return $assignment->load(['citizenReport.reportLocation', 'officer']);
            }

            $now = now();

            $assignment->update([
                'responded_at' => $assignment->responded_at ?? $now,
                'notes' => $notes ?? $assignment->notes,
            ]);

            $report->update([
                'status' => 'in_progress',
                'accepted_at' => $report->accepted_at ?? $now,
            ]);

            OfficerLiveLocation::query()
                ->where('officer_id', $officer->id)
                ->update([
                    'availability_status' => 'responding',
                    'updated_at' => $now,
                    'last_update_time' => $now,
                ]);

            return $assignment->fresh(['citizenReport.reportLocation', 'officer']);
        });

        return $startedAssignment;
    }

    public function completeAssignment(
        ReportAssignment $assignment,
        User $officer,
        ?string $notes = null
    ): ReportAssignment {
        $completedAssignment = DB::transaction(function () use ($assignment, $officer, $notes) {
            $assignment = ReportAssignment::query()
                ->whereKey($assignment->id)
                ->where('officer_id', $officer->id)
                ->lockForUpdate()
                ->firstOrFail();

            $report = CitizenReport::query()
                ->whereKey($assignment->citizen_report_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($assignment->assignment_status === 'completed') {
                return $assignment->load(['citizenReport.reportLocation', 'officer']);
            }

            $now = now();

            $assignment->update([
                'assignment_status' => 'completed',
                'responded_at' => $now,
                'notes' => $notes,
            ]);

            $report->update([
                'status' => 'closed',
                'assigned_officer_id' => $officer->id,
                'accepted_at' => $report->accepted_at ?? $now,
                'closed_at' => $now,
            ]);

            OfficerLiveLocation::query()
                ->where('officer_id', $officer->id)
                ->update([
                    'availability_status' => 'available',
                    'updated_at' => $now,
                    'last_update_time' => $now,
                ]);

            return $assignment->fresh(['citizenReport.reportLocation', 'officer']);
        });

        $this->dispatchPendingReportsForOfficer($officer->id);

        return $completedAssignment;
    }

    public function retryPendingAssignments(): void
    {
        $this->dispatchPendingReports();
    }

    public function expireStaleAssignments(): void
    {
        $this->retryPendingAssignments();
    }

    public function dispatchPendingReports(int $limit = 50): int
    {
        $reports = CitizenReport::query()
            ->with('reportLocation')
            ->where('status', 'submitted')
            ->whereNull('assigned_officer_id')
            ->whereNotNull('report_location_id')
            ->where(function ($query): void {
                $query->whereNull('last_dispatch_at')
                    ->orWhere('last_dispatch_at', '<=', now()->subSeconds(self::PENDING_RETRY_COOLDOWN_SECONDS));
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('submitted_at')
            ->limit($limit)
            ->get();

        $dispatchedCount = 0;

        foreach ($reports as $report) {
            if ($this->dispatchReport($report) !== null) {
                $dispatchedCount++;
            }
        }

        return $dispatchedCount;
    }

    public function dispatchPendingReportsForOfficer(int $officerId, int $limit = 50): int
    {
        $reports = CitizenReport::query()
            ->with('reportLocation')
            ->where('status', 'submitted')
            ->whereNull('assigned_officer_id')
            ->whereNotNull('report_location_id')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('submitted_at')
            ->limit($limit)
            ->get();

        $dispatchedCount = 0;

        foreach ($reports as $report) {
            if ($this->dispatchReport($report, $this->allOfficerIdsExcept($officerId)) !== null) {
                $dispatchedCount++;
            }
        }

        return $dispatchedCount;
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

    private function allOfficerIdsExcept(int $officerId): array
    {
        return User::query()
            ->where('role', RoleUserEnum::Police_officer)
            ->whereKeyNot($officerId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }
}
