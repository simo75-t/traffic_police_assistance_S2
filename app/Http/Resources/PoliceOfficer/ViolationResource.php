<?php

namespace App\Http\Resources\PoliceOfficer;

use App\Http\Resources\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'vehicle_id' => $this->vehicle_id,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),

            'violation_type_id' => $this->violation_type_id,
            'type' => new ViolationTypeResource($this->whenLoaded('violationType')),

            'violation_location_id' => $this->violation_location_id,
            'location' => new ViolationLocationResource($this->whenLoaded('violationLocation')),

            'reported_by' => $this->reported_by,
            'reporter' => new ProfileResource($this->whenLoaded('reported_by')),

            'description' => $this->description,
            'fine_amount' => (float) $this->fine_amount,
            'vehicle_snapshot' => $this->vehicle_snapshot,

            'occurred_at' => $this->occurred_at ? $this->occurred_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * إذا كنت تريد ترسل بيانات ميتا إضافية
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource' => 'violation',
            ],
        ];
    }
}
