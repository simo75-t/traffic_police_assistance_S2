<?php

namespace App\Http\Services\PoliceOfficer;

use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
 use Carbon\Carbon;


class ViolationService
{


    public function getViolationList()
    {
        $violations = Violation::where('reported_by', Auth::id())
            ->with(['violationLocation.city', 'violationType', 'vehicle'])
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

        $location = ViolationLocation::create([
            'city_id'     => $data['city_id'],
            'street_name' => $data['street_name'],
            'landmark'    => $data['landmark'] ?? null,
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
    $violations = Violation::with(['violationLocation.city', 'violationType', 'vehicle'])
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
        ->orderBy(
            $params['order_by'] ?? 'occurred_at',
            $params['order_direction'] ?? 'desc'
        );

    // dd($violations->toSql(), $violations->getBindings());

    return $violations->paginate($params['per_page'] ?? 10);
}

}
