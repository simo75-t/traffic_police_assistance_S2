<?php

namespace App\Http\Resources\PoliceOfficer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $report = $this->whenLoaded('citizenReport');
        $location = $report && $report->relationLoaded('reportLocation')
            ? $report->reportLocation
            : null;

        return [
            'assignment_id' => $this->id,
            'assignment_order' => $this->assignment_order,
            'distance_km' => $this->distance_km,
            'assigned_at' => $this->assigned_at,
            'notes' => $this->notes,
            'report' => $report ? [
                'id' => $report->id,
                'title' => $report->title,
                'description' => $report->description,
                'status' => $report->status,
                'priority' => $report->priority,
                'image_url' => $report->image_path ? asset($report->image_path) : null,
                'submitted_at' => $report->submitted_at,
                'location' => $location ? [
                    'address' => $location->address,
                    'street_name' => $location->street_name,
                    'landmark' => $location->landmark,
                    'city' => $location->city,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ] : null,
                'reporter' => [
                    'name' => $report->reporter_name,
                    'phone' => $report->reporter_phone,
                ],
            ] : null,
        ];
    }
}
