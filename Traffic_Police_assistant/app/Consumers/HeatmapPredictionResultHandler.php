<?php

namespace App\Consumers;

use App\Logging\HeatmapPredictionLogger;
use App\Services\Heatmap\HeatmapPredictionRecordService;

class HeatmapPredictionResultHandler
{
    public function __construct(
        private readonly HeatmapPredictionRecordService $records,
        private readonly HeatmapPredictionLogger $logger,
    ) {
    }

    public function handle(?string $requestId, array $data): void
    {
        if (! $requestId) {
            $this->logger->resultMissingRequestId();

            return;
        }

        $this->logger->resultReceived($requestId);

        if (! $this->records->applyResult($requestId, $data)) {
            $this->logger->resultNotFound($requestId);
        }
    }
}
