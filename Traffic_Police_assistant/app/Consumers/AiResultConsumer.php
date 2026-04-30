<?php

namespace App\Consumers;

use App\Enums\AiJobType;

class AiResultConsumer
{
    public function __construct(
        private readonly AiJobResultHandler $jobResults,
        private readonly HeatmapPredictionResultHandler $predictionResults,
    ) {
    }

    public function consume(string $body): void
    {
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $type = (string) ($data['type'] ?? '');
        $jobId = $data['job_id'] ?? $data['request_id'] ?? null;

        if ($type === AiJobType::GenerateHeatmapPrediction->value) {
            $this->predictionResults->handle($jobId, $data);

            return;
        }

        $this->jobResults->handle($data);
    }
}
