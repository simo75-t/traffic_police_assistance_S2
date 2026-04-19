<?php

namespace App\Http\Services\PoliceOfficer;

use App\Models\Area;
use App\Models\City;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
 use Carbon\Carbon;


class ViolationService
{
    private function normalizeCityName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(
            ['أ', 'إ', 'آ', 'ة', 'ى'],
            ['ا', 'ا', 'ا', 'ه', 'ي'],
            $normalized
        );

        return mb_strtolower($normalized, 'UTF-8');
    }


    public function getViolationList()
    {
        $violations = Violation::where('reported_by', Auth::id())
            ->with(['violationLocation.city', 'violationLocation.area', 'violationType', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $violations;
    }

   public function createViolation(array $data)
{
    return DB::transaction(function () use ($data) {

        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => $data['vehicle_plate']],
            [
                'color'      => $data['vehicle_color'] ?? null,
                'model'      => $data['vehicle_model'] ?? null,
                'brand'      => $data['vehicle_brand'] ?? null,
                'owner_name' => $data['vehicle_owner'] ?? null,
            ]
        );

        $violationType = \App\Models\ViolationType::findOrFail($data['violation_type_id']);

        $cityId = $data['city_id'] ?? null;
        $cityName = $data['city_name'] ?? null;

        if (empty($cityId) && !empty($cityName)) {
            $normalizedIncomingCity = $this->normalizeCityName($cityName);

            $matchedCity = City::query()
                ->get()
                ->first(function (City $city) use ($normalizedIncomingCity) {
                    $normalizedStoredCity = $this->normalizeCityName($city->name);

                    return $normalizedStoredCity !== null
                        && $normalizedIncomingCity !== null
                        && (
                            $normalizedStoredCity === $normalizedIncomingCity ||
                            str_contains($normalizedStoredCity, $normalizedIncomingCity) ||
                            str_contains($normalizedIncomingCity, $normalizedStoredCity)
                        );
                });

            if ($matchedCity) {
                $cityId = $matchedCity->id;
                $cityName = $matchedCity->name;
            }
        }

        if ($cityId && empty($cityName)) {
            $cityName = City::query()->find($cityId)?->name;
        }

        $areaId = $data['area_id'] ?? null;
        $areaName = $data['area_name'] ?? null;

        if (empty($areaId) && !empty($areaName)) {
            $normalizedAreaName = $this->normalizeCityName($areaName);
            $matchedArea = Area::query()
                ->get()
                ->first(function (Area $area) use ($normalizedAreaName) {
                    $normalizedStoredArea = $this->normalizeCityName($area->name);
                    return $normalizedStoredArea !== null
                        && $normalizedAreaName !== null
                        && (
                            $normalizedStoredArea === $normalizedAreaName ||
                            str_contains($normalizedStoredArea, $normalizedAreaName) ||
                            str_contains($normalizedAreaName, $normalizedStoredArea)
                        );
                });

            if ($matchedArea) {
                $areaId = $matchedArea->id;
            }
        }

        $location = ViolationLocation::create([
            'area_id'     => $areaId,
            'city_id'     => $cityId,
            'city'        => $cityName,
            'street_name' => $data['street_name'] ?? null,
            'landmark'    => $data['landmark'] ?? null,
            'latitude'    => $data['latitude'] ?? null,
            'longitude'   => $data['longitude'] ?? null,
        ]);

        $occurredAt = !empty($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        $violation = Violation::create([
            'vehicle_id'            => $vehicle->id,
            'violation_type_id'     => $data['violation_type_id'],
            'violation_location_id' => $location->id,
            'reported_by'           => Auth::id(),
            'description'           => $data['description'] ?? null,
            'fine_amount'           => $violationType->fine_amount,

            'vehicle_snapshot'      => json_encode([
                'plate_number' => $vehicle->plate_number,
                'owner_name'   => $data['vehicle_owner'] ?? null,
            ]),

            'occurred_at'           => $occurredAt,
        ]);

        return $violation;
    });
}


    public function getAllViolationList(array $params = [])
{
    $violations = Violation::with(['violationLocation.city', 'violationLocation.area', 'violationType', 'vehicle'])
        ->when(isset($params['plate']) && $params['plate'] !== '', function ($q) use ($params) {
            $q->whereHas('vehicle', function ($q2) use ($params) {
                $q2->where('plate_number', 'like', '%' . $params['plate'] . '%');
            });
        })
        ->when(isset($params['from']) && $params['from'] !== '', function ($q) use ($params) {
            $q->whereDate('occurred_at', '>=', $params['from']);
        })
        ->when(isset($params['to']) && $params['to'] !== '', function ($q) use ($params) {
            $q->whereDate('occurred_at', '<=', $params['to']);
        })
        ->when(isset($params['violation_type_id']) && $params['violation_type_id'] !== '', function ($q) use ($params) {
            $q->where('violation_type_id', (int) $params['violation_type_id']);
        })
        ->when(isset($params['city']) && trim((string) $params['city']) !== '', function ($q) use ($params) {
            $requestedCity = $this->normalizeCityName((string) $params['city']);

            $q->whereHas('violationLocation', function ($locationQuery) use ($requestedCity) {
                $locationQuery
                    ->whereRaw('LOWER(city) = ?', [$requestedCity])
                    ->orWhereHas('cityRecord', function ($cityQuery) use ($requestedCity) {
                        $cityQuery->whereRaw('LOWER(name) = ?', [$requestedCity]);
                    });
            });
        })
        ->orderBy(
            $params['order_by'] ?? 'occurred_at',
            $params['order_direction'] ?? 'desc'
        );

    // dd($violations->toSql(), $violations->getBindings());

    return $violations->paginate($params['per_page'] ?? 10);
}

}
