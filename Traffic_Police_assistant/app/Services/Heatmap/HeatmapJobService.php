<?php

namespace App\Services\Heatmap;

use App\DTOs\HeatmapJobData;
use App\Integrations\Messaging\RabbitMqPublisher;
use App\Models\AiJob;
use Illuminate\Support\Str;

class HeatmapJobService
{
    private const JOB_TYPE = 'generate_heatmap';

    public function __construct(
        private readonly RabbitMqPublisher $publisher,
    ) {
    }

    public function queue(array $validated, ?int $requestedBy): AiJob
    {
        $jobId = (string) Str::uuid();
        $correlationId = (string) Str::uuid();
        $payload = HeatmapJobData::fromValidated($validated)->toPayload($jobId, $correlationId);

        $job = AiJob::query()->create([
            'job_id' => $jobId,
            'correlation_id' => $correlationId,
            'type' => self::JOB_TYPE,
            'status' => 'queued',
            'requested_by' => $requestedBy,
            'payload' => $payload,
        ]);

        $this->publisher->publish(
            config('ai_rmq.routing_keys.heatmap'),
            $payload,
            config('ai_rmq.queues.heatmap')
        );

        return $job;
    }

    public function find(string $jobId): AiJob
    {
        return AiJob::query()
            ->where('job_id', $jobId)
            ->where('type', self::JOB_TYPE)
            ->firstOrFail();
    }
}
