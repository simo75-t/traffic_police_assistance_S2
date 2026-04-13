<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $location = $this->whenLoaded('reportLocation');
        $assignedOfficer = $this->whenLoaded('assignedOfficer');
        $latestAssignment = $this->relationLoaded('assignments')
            ? $this->assignments->sortByDesc('assignment_order')->first()
            : null;

        return [
            'id' => $this->id,
            'reporter_name' => $this->reporter_name,
            'reporter_phone' => $this->reporter_phone,
            'reporter_email' => $this->reporter_email,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? asset($this->image_path) : null,
            'submitted_at' => $this->submitted_at,
            'accepted_at' => $this->accepted_at,
            'closed_at' => $this->closed_at,
            'dispatch_attempts_count' => $this->dispatch_attempts_count,
            'last_dispatch_at' => $this->last_dispatch_at,
            'location' => $location ? [
                'id' => $location->id,
                'address' => $location->address,
                'street_name' => $location->street_name,
                'landmark' => $location->landmark,
                'city' => $location->city,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
            ] : null,
            'assigned_officer' => $assignedOfficer ? [
                'id' => $assignedOfficer->id,
                'name' => $assignedOfficer->name,
                'email' => $assignedOfficer->email,
                'phone' => $assignedOfficer->phone,
            ] : null,
            'latest_assignment' => $latestAssignment ? [
                'id' => $latestAssignment->id,
                'officer_id' => $latestAssignment->officer_id,
                'assignment_order' => $latestAssignment->assignment_order,
                'distance_km' => $latestAssignment->distance_km,
                'assignment_status' => $latestAssignment->assignment_status,
                'assigned_at' => $latestAssignment->assigned_at,
                'responded_at' => $latestAssignment->responded_at,
                'response_deadline' => $latestAssignment->response_deadline,
                'notes' => $latestAssignment->notes,
            ] : null,
        ];
    }
}
