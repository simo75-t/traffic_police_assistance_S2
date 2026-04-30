<?php

namespace App\Services\Heatmap;

use App\DTOs\HeatmapPredictionJobData;
use App\Enums\PredictionStatus;
use App\Integrations\Messaging\RabbitMqPublisher;
use App\Logging\HeatmapPredictionLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HeatmapPredictionJobService
{
    public function __construct(
        private readonly RabbitMqPublisher $publisher,
        private readonly HeatmapPredictionRecordService $records,
        private readonly HeatmapPredictionLogger $logger,
    ) {
    }

    public function queue(array $validated, ?int $requestedBy): Model
    {
        $jobId = (string) Str::uuid();
        $correlationId = (string) Str::uuid();
        $payload = HeatmapPredictionJobData::fromValidated($validated)->toPayload($jobId, $correlationId);

        $prediction = $this->records->createPending($jobId, $correlationId, $payload, $requestedBy);
        $this->logger->created($jobId, PredictionStatus::Pending->value);

        try {
            $this->publisher->publish(
                config('ai_rmq.routing_keys.heatmap_prediction'),
                $payload,
                config('ai_rmq.queues.heatmap_prediction')
            );
            $this->logger->published($jobId, $correlationId);
            $this->records->markProcessing($prediction);
        } catch (\Throwable $exception) {
            $this->records->markPublishFailure($prediction, $exception->getMessage());

            throw $exception;
        }

        return $prediction->fresh() ?? $prediction;
    }

    public function find(string $requestId): Model
    {
        $this->logger->statusRequested($requestId);

        return $this->records->find($requestId);
    }
}
