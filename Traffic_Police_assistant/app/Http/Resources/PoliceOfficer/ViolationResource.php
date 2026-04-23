<?php

namespace App\Http\Resources\PoliceOfficer;

use App\Http\Resources\ProfileResource;
use App\Http\Services\PoliceOfficer\ViolationPdfService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationResource extends JsonResource
{
    public function toArray(Request $request): array
{
    $pdfService = app(ViolationPdfService::class);
    $vehicleSnapshot = $this->vehicle_snapshot;

    if (is_string($vehicleSnapshot)) {
        $decoded = json_decode($vehicleSnapshot, true);
        $vehicleSnapshot = is_array($decoded) ? $decoded : null;
    }

    return [
        'id'             => $this->id,
        'vehicle'        => new VehicleResource($this->whenLoaded('vehicle')),
        'violation_type' => new ViolationTypeResource($this->whenLoaded('violationType')),
        'location'       => new ViolationLocationResource($this->whenLoaded('violationLocation')),
        'description'    => $this->description,
        'fine_amount'    => (float) $this->fine_amount,
        'vehicle_snapshot' => $vehicleSnapshot,
        'plate_snapshot' => $this->plate_snapshot,
        'owner_snapshot' => $this->owner_snapshot,
        'source_report_id' => $this->source_report_id,
        'data_source' => $this->data_source,
        'is_synthetic' => (bool) $this->is_synthetic,
        'severity_level' => $this->severity_level,
        'status' => $this->status,
        'pdf_path' => $this->pdf_path,
        'pdf_url' => $pdfService->pdfUrl($this->pdf_path),
        'appeal'         => $this->appeal ? [
            'id'          => $this->appeal->id,
            'status'      => $this->appeal->status,
            'reason'      => $this->appeal->reason,
            'decision_note' => $this->appeal->decision_note,
            'created_at'  => $this->appeal->created_at,
            'updated_at'  => $this->appeal->updated_at,
        ] : null,
       'occurred_at' => $this->occurred_at instanceof CarbonInterface
           ? $this->occurred_at->toIso8601String()
           : null,
'created_at'  => $this->created_at instanceof CarbonInterface
    ? $this->created_at->toIso8601String()
    : null,
'updated_at'  => $this->updated_at instanceof CarbonInterface
    ? $this->updated_at->toIso8601String()
    : null,

    ];
}


    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource' => 'violation',
            ],
        ];
    }
}
