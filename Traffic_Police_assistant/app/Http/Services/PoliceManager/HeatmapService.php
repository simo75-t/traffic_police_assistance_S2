<?php

namespace App\Http\Services\PoliceManager;

use App\Models\AiJob;
use App\Services\Heatmap\HeatmapJobService;

class HeatmapService
{
    public function __construct(
        private readonly HeatmapJobService $service,
    ) {
    }

    public function queueJob(array $validated, ?int $requestedBy): AiJob
    {
        return $this->service->queue($validated, $requestedBy);
    }

    public function findJob(string $jobId): AiJob
    {
        return $this->service->find($jobId);
    }
}
