<?php

namespace App\Http\Resources\PoliceManager;

use App\Enums\PredictionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeatmapPredictionResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $normalizedStatus = PredictionStatus::normalize((string) $this->status)->value;
        $result = is_array($this->result) ? $this->result : [];
        $payload = is_array($this->payload) ? $this->payload : [];
        $source = ($this->source ?? null) ?: ($result['source'] ?? null);
        $errorMessage = $this->error_message
            ?? (is_array($this->error ?? null) ? ($this->error['message'] ?? null) : ($this->error ?? null));
        $data = null;

        if ($normalizedStatus !== PredictionStatus::Processing->value) {
            $data = [
                'request_id' => (string) ($result['request_id'] ?? $this->request_id ?? $this->job_id),
                'city' => $result['city'] ?? ($payload['heatmap_summary']['city'] ?? null),
                'source' => $source,
                'signal_summary' => is_array($result['signal_summary'] ?? null) ? $result['signal_summary'] : [],
                'prediction_summary' => $result['prediction_summary'] ?? null,
                'overall_risk_level' => $result['overall_risk_level'] ?? null,
                'predicted_hotspots' => is_array($result['predicted_hotspots'] ?? null) ? $result['predicted_hotspots'] : [],
                'recommendations' => is_array($result['recommendations'] ?? null) ? $result['recommendations'] : [],
                'limitations' => is_array($result['limitations'] ?? null) ? $result['limitations'] : [],
            ];
        }

        return [
            'request_id' => $this->request_id ?? $this->job_id,
            'job_id' => $this->request_id ?? $this->job_id,
            'status' => $normalizedStatus,
            'source' => $normalizedStatus === PredictionStatus::Processing->value ? null : $source,
            'data' => $data,
            'error_message' => $errorMessage,
        ];
    }
}
