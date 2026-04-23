<?php

namespace App\Http\Services\Citizen;

use App\Http\Services\Dispatch\DispatchService;
use App\Models\Area;
use App\Models\CitizenReport;
use App\Models\ReportLocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private readonly DispatchService $dispatchService
    ) {
    }

    public function createReport(array $data): CitizenReport
    {
        $report = DB::transaction(function () use ($data) {
            $resolvedArea = $this->resolveAreaFromCoordinates(
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            $location = ReportLocation::query()->create([
                'area_id' => $resolvedArea?->id,
                'address' => $data['address'] ?? null,
                'street_name' => $data['street_name'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'city' => $data['city'] ?? $resolvedArea?->city,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'created_at' => now(),
            ]);

            $imagePath = isset($data['image']) && $data['image'] instanceof UploadedFile
                ? $this->storeReportImage($data['image'])
                : null;

            return CitizenReport::query()->create([
                'reporter_name' => $data['reporter_name'],
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'report_location_id' => $location->id,
                'title' => $data['title'], 
                'description' => $data['description'],
                'image_path' => $imagePath,
                'status' => 'submitted',
                'priority' => $data['priority'] ?? 'medium',
                'submitted_at' => now(),
                'created_at' => now(),
                'dispatch_attempts_count' => 0,
            ]);
        });

        $this->dispatchService->retryPendingAssignments();
        $this->dispatchService->dispatchReport($report->load('reportLocation'));

        return $report->fresh([
            'reportLocation.area',
            'assignedOfficer',
            'assignments.officer',
        ]);
    }

    private function resolveAreaFromCoordinates(float $latitude, float $longitude): ?Area
    {
        /** @var Collection<int, Area> $areas */
        $areas = Area::query()
            ->whereNotNull('center_lat')
            ->whereNotNull('center_lng')
            ->get();

        if ($areas->isEmpty()) {
            return null;
        }

        $closest = $areas
            ->map(function (Area $area) use ($latitude, $longitude): array {
                return [
                    'area' => $area,
                    'distance_km' => $this->haversineDistanceKm(
                        $latitude,
                        $longitude,
                        (float) $area->center_lat,
                        (float) $area->center_lng
                    ),
                ];
            })
            ->sortBy('distance_km')
            ->first();

        if (! $closest || $closest['distance_km'] > 100) {
            return null;
        }

        return $closest['area'];
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

    private function storeReportImage(UploadedFile $file): string
    {
        $directory = public_path('uploads/reports');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'report_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/reports/' . $filename;
    }
}
