<?php

namespace App\Http\Services\PoliceManager;

use App\Models\Violation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
}

