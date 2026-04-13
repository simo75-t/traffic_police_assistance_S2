<?php

namespace App\Http\Services\PoliceManager;

use App\Models\City;
use App\Models\ViolationType;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class ViolationService
{
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
