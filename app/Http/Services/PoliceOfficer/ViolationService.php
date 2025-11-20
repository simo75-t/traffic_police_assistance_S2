<?php

namespace App\Http\Services\PoliceOfficer;

use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViolationService
{


    public function getViolationList()
    {
        $violations = Violation::where('reported_by', Auth::id())
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

            $vehicleSnapshot = [
                'plate_number' => $vehicle->plate_number,
                'owner_name'   => $vehicle->owner_name,
            ];
            
            $violationType = \App\Models\ViolationType::findOrFail($data['violation_type_id']);


            $location = ViolationLocation::create([
                'steet_name' => $data['steet_name'],
                'address'     => $data['address'] ?? null,
                'land_mark'   => $data['land_mark'] ?? null,
                
            ]);

            $violation = Violation::create([
                'vehicle_id'            => $vehicle->id,
                'violation_type_id'     => $data['violation_type_id'],
                'violation_location_id' => $location->id,
                'reported_by'           => Auth::id(),
                'description'           => $data['description'] ?? null,
                'fine_amount'           => $violationType->fine_amount,
                'vehicle_snapshot'      => json_encode($vehicleSnapshot),
                'occurred_at'           => $data['occurred_at'],
            ]);

            return $violation;
        }, ); 


}
}
