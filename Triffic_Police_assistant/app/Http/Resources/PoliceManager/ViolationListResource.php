<?php

namespace App\Http\Resources\PoliceManager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Violation */
class ViolationListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'vehicle_plate_number' => $this->vehicle?->plate_number ?? (string) $this->vehicle_id,
            'violation_type_name' => $this->violationType?->name ?? '-',
            'street_name' => $this->violationLocation?->street_name
                ?? $this->violationLocation?->steet_name
                ?? '-',
            'fine_amount' => (float) $this->fine_amount,
            'reporter_name' => $this->reporter?->name ?? '-',
            'occurred_at' => (string) $this->occurred_at,
        ];
    }
}

