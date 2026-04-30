<?php

namespace App\DTOs;

use App\Enums\AiJobType;

final class HeatmapPredictionJobData
{
    public function __construct(
        public readonly array $heatmapSummary,
    ) {
    }

    public static function fromValidated(array $validated): self
    {
        return new self(
            heatmapSummary: $validated['heatmap_summary'],
        );
    }

    public function toPayload(string $jobId, string $correlationId): array
    {
        return [
            'job_type' => AiJobType::GenerateHeatmapPrediction->value,
            'request_id' => $jobId,
            'job_id' => $jobId,
            'correlation_id' => $correlationId,
            'heatmap_summary' => $this->heatmapSummary,
        ];
    }
}
