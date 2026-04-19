<?php

namespace App\Http\Services\PoliceManager;

use App\Models\City;
use App\Models\CitizenReport;
use App\Models\ViolationType;
use App\Models\Violation;
use App\Http\Services\Dispatch\DispatchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class ViolationService
{
    public function __construct(private readonly DispatchService $dispatchService)
    {
    }

    /**
     * @param array{search_type?: string|null, search?: string|null} $filters
     * @return Collection<int, \App\Models\Violation>
     */
    public function getFilteredViolations(array $filters): Collection
    {
        $searchType = (string) ($filters['search_type'] ?? '');
        $searchValue = trim((string) ($filters['search'] ?? ''));

        return Violation::query()
            ->with(['vehicle', 'reporter', 'violationType', 'violationLocation'])
            ->when($searchType !== '' && $searchValue !== '', function (Builder $query) use ($searchType, $searchValue): void {
                match ($searchType) {
                    'vehicle_id' => $query->whereHas('vehicle', function (Builder $subQuery) use ($searchValue): void {
                        $subQuery->where('plate_number', 'like', '%' . $searchValue . '%');
                    }),
                    'violation_type' => $query->whereHas('violationType', function (Builder $subQuery) use ($searchValue): void {
                        $subQuery->where('name', 'like', '%' . $searchValue . '%');
                    }),
                    'reporter' => $query->whereHas('reporter', function (Builder $subQuery) use ($searchValue): void {
                        $subQuery->where('name', 'like', '%' . $searchValue . '%');
                    }),
                    'occurred_at' => $query->where('occurred_at', 'like', '%' . $searchValue . '%'),
                    default => null,
                };
            })
            ->latest('occurred_at')
            ->get();
    }

    /**
     * Prepare selector data and defaults for the AI heatmap page.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getHeatmapPageData(array $filters): array
    {
        return [
            'filters' => $this->buildHeatmapFilters($filters),
            'violationTypes' => ViolationType::query()->orderBy('name')->get(),
            'cities' => City::query()->orderBy('name')->get(),
            'timeBucketOptions' => [
                '' => 'All Day',
                'morning' => 'Morning',
                'afternoon' => 'Afternoon',
                'evening' => 'Evening',
                'night' => 'Night',
            ],
            'comparisonModeOptions' => [
                '' => 'None',
                'week_over_week' => 'Week Over Week',
                'month_over_month' => 'Month Over Month',
            ],
        ];
    }

    private function normalizeReportStatus(?string $status): string
    {
        return match ($status) {
            'under_review' => 'in_progress',
            null => 'unknown',
            default => $status,
        };
    }

    public function getReportsMapPageData(array $filters = []): array
    {
        $normalizedStatus = trim((string) ($filters['status'] ?? ''));
        $normalizedCity = trim((string) ($filters['city'] ?? ''));
        $normalizedAssignment = trim((string) ($filters['assignment'] ?? ''));

        $reports = CitizenReport::query()
            ->with(['assignedOfficer', 'reportLocation.area', 'assignments.officer'])
            ->latest('submitted_at')
            ->get();

        $dispatchedAny = false;
        foreach ($reports as $report) {
            if ($report->assigned_officer_id === null
                && $report->reportLocation?->latitude !== null
                && $report->reportLocation?->longitude !== null
            ) {
                $assignment = $this->dispatchService->dispatchReport($report);
                if ($assignment) {
                    $dispatchedAny = true;
                }
            }
        }

        if ($dispatchedAny) {
            $reports = CitizenReport::query()
                ->with(['assignedOfficer', 'reportLocation.area', 'assignments.officer'])
                ->latest('submitted_at')
                ->get();
        }

        $reportsMap = $reports->map(function (CitizenReport $report) {
            $status = $this->normalizeReportStatus($report->status);
            $location = $report->reportLocation;
            $city = $location?->city ?? $location?->area?->city;
            $assignmentState = $report->assigned_officer_id ? 'assigned' : 'unassigned';
            $locationSummary = collect([
                $city,
                $location?->street_name,
                $location?->landmark,
                $location?->address,
            ])->filter(fn (?string $value) => filled($value))->implode(' - ');

            return [
                'id' => $report->id,
                'title' => $report->title,
                'description' => $report->description,
                'status' => $status,
                'priority' => $report->priority,
                'submitted_at' => $report->submitted_at ? Carbon::parse($report->submitted_at)->toDateTimeString() : null,
                'created_at' => $report->created_at ? Carbon::parse($report->created_at)->toDateTimeString() : null,
                'accepted_at' => $report->accepted_at ? Carbon::parse($report->accepted_at)->toDateTimeString() : null,
                'closed_at' => $report->closed_at ? Carbon::parse($report->closed_at)->toDateTimeString() : null,
                'assigned_officer_id' => $report->assigned_officer_id,
                'assigned_officer' => $report->assignedOfficer?->name,
                'assignment_state' => $assignmentState,
                'assignment_count' => $report->assignments->count(),
                'reporter_name' => $report->reporter_name,
                'reporter_phone' => $report->reporter_phone,
                'location_summary' => $locationSummary,
                'location' => [
                    'latitude' => $location?->latitude,
                    'longitude' => $location?->longitude,
                    'landmark' => $location?->landmark,
                    'address' => $location?->address,
                    'street_name' => $location?->street_name,
                    'city' => $city,
                ],
            ];
        })
            ->filter(function (array $report) use ($normalizedStatus, $normalizedCity, $normalizedAssignment): bool {
                if ($normalizedStatus !== '' && $report['status'] !== $normalizedStatus) {
                    return false;
                }

                if ($normalizedAssignment !== '' && $report['assignment_state'] !== $normalizedAssignment) {
                    return false;
                }

                if ($normalizedCity !== '') {
                    $city = (string) data_get($report, 'location.city', '');

                    if (! str_contains(mb_strtolower($city), mb_strtolower($normalizedCity))) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        return [
            'reports' => $reports,
            'reportsMap' => $reportsMap->all(),
            'summary' => [
                'totalReports' => $reportsMap->count(),
                'assignedReports' => $reportsMap->where('assignment_state', 'assigned')->count(),
                'unassignedReports' => $reportsMap->where('assignment_state', 'unassigned')->count(),
                'pendingReports' => $reportsMap->whereIn('status', ['submitted', 'dispatched', 'in_progress'])->count(),
                'closedReports' => $reportsMap->where('status', 'closed')->count(),
            ],
            'filters' => [
                'status' => $normalizedStatus,
                'city' => $normalizedCity,
                'assignment' => $normalizedAssignment,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    private function buildHeatmapFilters(array $filters): array
    {
        $defaultFrom = Carbon::now()->subDays(6)->toDateString();
        $defaultTo = Carbon::now()->toDateString();

        return [
            'city' => (string) ($filters['city'] ?? ''),
            'date_from' => (string) ($filters['date_from'] ?? $defaultFrom),
            'date_to' => (string) ($filters['date_to'] ?? $defaultTo),
            'violation_type_id' => (string) ($filters['violation_type_id'] ?? ''),
            'time_bucket' => (string) ($filters['time_bucket'] ?? ''),
            'grid_size_meters' => (string) ($filters['grid_size_meters'] ?? '300'),
            'include_ranking' => !empty($filters['include_ranking']) ? '1' : '0',
            'include_trend' => !empty($filters['include_trend']) ? '1' : '0',
            'include_synthetic' => !empty($filters['include_synthetic']) ? '1' : '0',
            'comparison_mode' => (string) ($filters['comparison_mode'] ?? ''),
        ];
    }
}
