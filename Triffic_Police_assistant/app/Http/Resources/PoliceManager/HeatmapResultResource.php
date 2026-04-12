<?php

namespace App\Http\Resources\PoliceManager;

use App\Models\AiJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiJob */
class HeatmapResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = is_array($this->result) ? $this->result : [];
        $meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $heatmapPoints = is_array($result['heatmap_points'] ?? null) ? $result['heatmap_points'] : [];
        $ranking = is_array($result['ranking'] ?? null) ? $result['ranking'] : [];
        $trend = is_array($result['trend'] ?? null) ? $result['trend'] : [];

        return [
            'job_id' => $this->job_id,
            'status' => (string) $this->status,
            'type' => (string) $this->type,
            'error' => $this->error,
            'data' => [
                'request_id' => (string) ($result['request_id'] ?? $this->job_id),
                'city' => $result['city'] ?? ($this->payload['city'] ?? null),
                'cache_key' => $result['cache_key'] ?? null,
                'meta' => [
                    'date_from' => $meta['date_from'] ?? ($this->payload['date_from'] ?? null),
                    'date_to' => $meta['date_to'] ?? ($this->payload['date_to'] ?? null),
                    'time_bucket' => $meta['time_bucket'] ?? ($this->payload['time_bucket'] ?? ''),
                    'grid_size_meters' => $meta['grid_size_meters'] ?? ($this->payload['grid_size_meters'] ?? null),
                    'total_violations' => $meta['total_violations'] ?? 0,
                    'from_cache' => (bool) ($meta['from_cache'] ?? false),
                ],
                'summary' => [
                    'points_count' => count($heatmapPoints),
                    'ranking_count' => count($ranking),
                    'trend_count' => count($trend),
                ],
                'heatmap_points' => $heatmapPoints,
                'ranking' => $ranking,
                'trend' => $trend,
            ],
        ];
    }
}
