<?php

namespace App\Http\Resources\PoliceManager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Appeal */
class AppealDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'violation_id' => $this->violation_id,
            'status' => (string) $this->status,
            'reason' => (string) $this->reason,
            'decision_note' => $this->decision_note,
            'submitted_at' => (string) $this->submitted_at,
            'decided_at' => (string) $this->decided_at,
            'created_at' => (string) $this->created_at,
        ];
    }
}

