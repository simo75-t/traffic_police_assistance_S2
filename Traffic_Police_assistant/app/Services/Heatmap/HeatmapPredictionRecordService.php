<?php

namespace App\Services\Heatmap;

use App\Enums\AiJobType;
use App\Enums\PredictionStatus;
use App\Logging\HeatmapPredictionLogger;
use App\Models\AiJob;
use App\Models\HeatmapPrediction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class HeatmapPredictionRecordService
{
    private const SOURCE_QWEN = 'qwen_api';
    private const SOURCE_FALLBACK_PREFIX = 'fallback';

    public function __construct(
        private readonly HeatmapPredictionLogger $logger,
    ) {
    }

    public function usesDedicatedPredictionTable(): bool
    {
        return Schema::hasTable('heatmap_predictions');
    }

    public function createPending(string $jobId, string $correlationId, array $payload, ?int $requestedBy): Model
    {
        if ($this->usesDedicatedPredictionTable()) {
            return HeatmapPrediction::query()->create([
                'request_id' => $jobId,
                'correlation_id' => $correlationId,
                'status' => PredictionStatus::Pending->value,
                'payload' => $payload,
                'result' => null,
                'source' => null,
                'error_message' => null,
                'started_at' => now(),
            ]);
        }

        $job = AiJob::query()->create([
            'job_id' => $jobId,
            'correlation_id' => $correlationId,
            'type' => AiJobType::GenerateHeatmapPrediction->value,
            'status' => PredictionStatus::Queued->value,
            'requested_by' => $requestedBy,
            'payload' => $payload,
            'result' => null,
            'error' => null,
        ]);

        return $this->decorateLegacyPrediction($job);
    }

    public function markProcessing(Model $prediction): void
    {
        $prediction->forceFill(['status' => PredictionStatus::Processing->value])->save();

        if ($prediction instanceof AiJob) {
            $this->decorateLegacyPrediction($prediction);
        }
    }

    public function markPublishFailure(Model $prediction, string $message): void
    {
        if ($prediction instanceof HeatmapPrediction) {
            $prediction->forceFill([
                'status' => PredictionStatus::Failed->value,
                'error_message' => $message,
                'completed_at' => now(),
            ])->save();

            return;
        }

        $prediction->forceFill([
            'status' => PredictionStatus::Failed->value,
            'error' => ['message' => $message],
            'finished_at' => now(),
        ])->save();

        $this->decorateLegacyPrediction($prediction, $message);
    }

    public function find(string $requestId): Model
    {
        if ($this->usesDedicatedPredictionTable()) {
            return HeatmapPrediction::query()
                ->where('request_id', $requestId)
                ->firstOrFail();
        }

        $job = AiJob::query()
            ->where('job_id', $requestId)
            ->where('type', AiJobType::GenerateHeatmapPrediction->value)
            ->firstOrFail();

        return $this->decorateLegacyPrediction($job);
    }

    public function applyResult(string $requestId, array $data): bool
    {
        if ($this->usesDedicatedPredictionTable()) {
            $prediction = HeatmapPrediction::query()->where('request_id', $requestId)->first();
            if (! $prediction) {
                return false;
            }

            $result = is_array($data['result'] ?? null) ? $data['result'] : null;
            $source = $result['source'] ?? null;
            $status = $this->resolveStatus($data, $source, $result);
            $errorMessage = $this->extractErrorMessage($data, $result);

            $prediction->forceFill([
                'status' => $status,
                'source' => $source,
                'result' => $result,
                'error_message' => $errorMessage,
                'completed_at' => now(),
            ])->save();

            $this->logger->statusChanged($requestId, $status, $source);

            return true;
        }

        $job = AiJob::query()
            ->where('job_id', $requestId)
            ->where('type', AiJobType::GenerateHeatmapPrediction->value)
            ->first();

        if (! $job) {
            return false;
        }

        $job->status = PredictionStatus::fromJobResult($data)->value;
        $job->result = $data['result'] ?? null;
        $job->error = $data['error'] ? ['message' => (string) $data['error']] : null;
        $job->finished_at = now();
        $job->save();

        $this->logger->statusChanged($requestId, $job->status);

        return true;
    }

    private function resolveStatus(array $data, ?string $source, ?array $result): string
    {
        if ($source === self::SOURCE_QWEN) {
            return PredictionStatus::Done->value;
        }

        if (is_string($source) && str_starts_with($source, self::SOURCE_FALLBACK_PREFIX)) {
            return PredictionStatus::Failed->value;
        }

        if (PredictionStatus::fromJobResult($data) === PredictionStatus::Success && $result !== null) {
            return PredictionStatus::Done->value;
        }

        return PredictionStatus::Failed->value;
    }

    private function extractErrorMessage(array $data, ?array $result): ?string
    {
        $message = (string) ($data['error'] ?? ($result['error_message'] ?? ''));
        $message = trim($message);

        return $message !== '' ? $message : null;
    }

    private function decorateLegacyPrediction(AiJob $job, ?string $errorMessage = null): AiJob
    {
        $job->setAttribute('request_id', $job->job_id);
        $job->setAttribute('source', null);
        $job->setAttribute(
            'error_message',
            $errorMessage ?? (is_array($job->error) ? ($job->error['message'] ?? null) : null)
        );

        return $job;
    }
}
