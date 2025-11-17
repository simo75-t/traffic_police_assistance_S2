<?php

namespace App\Http\Services\PoliceOfficer;

use App\Models\Violation;
use Illuminate\Support\Facades\Auth;

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
        return Violation::create([
            'vehicle_id'             => $data['vehicle_id'],
            'violation_type_id'      => $data['violation_type_id'],
            'violation_location_id'  => $data['violation_location_id'],
            'reported_by'            => Auth::id(),  
            'description'            => $data['description'] ?? null,
            'fine_amount'            => $data['fine_amount'],
            'vehicle_snapshot'       => $data['vehicle_snapshot'], 
            'occurred_at'            => $data['occurred_at'],
        ]);
    }
}
