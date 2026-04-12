<?php

namespace App\Http\Resources\PoliceManager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Appeal */
class AppealListResource extends JsonResource
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
            'created_at' => (string) $this->created_at,
        ];
    }
}

