<?php

namespace App\Http\Services\PoliceManager;

use App\Services\Heatmap\HeatmapPredictionJobService;
use Illuminate\Database\Eloquent\Model;

class HeatmapPredictionService
{
    public function __construct(
        private readonly HeatmapPredictionJobService $service,
    ) {
    }

    public function queueJob(array $validated, ?int $requestedBy): Model
    {
        return $this->service->queue($validated, $requestedBy);
    }

    public function findPrediction(string $requestId): Model
    {
        return $this->service->find($requestId);
    }
}
