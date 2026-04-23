<?php

namespace App\Http\Services\PoliceManager;

use App\Http\Services\PoliceOfficer\RabbitPublisher;
use App\Models\AiJob;
use Illuminate\Support\Str;

class HeatmapService
{
    public function __construct(
        private readonly RabbitPublisher $publisher,
    ) {
    }

    public function queueJob(array $validated, ?int $requestedBy): AiJob
    {
        $includeTrend = (bool) ($validated['include_trend'] ?? false);
        $comparisonMode = $includeTrend
            ? (string) ($validated['comparison_mode'] ?? '')
            : '';

        $jobId = (string) Str::uuid();
        $corrId = (string) Str::uuid();

        $payload = [
            'job_type' => 'generate_heatmap',
            'request_id' => $jobId,
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'city' => trim((string) $validated['city']),
            'date_from' => (string) $validated['date_from'],
            'date_to' => (string) $validated['date_to'],
            'violation_type_id' => $validated['violation_type_id'] ?? null,
            'time_bucket' => (string) ($validated['time_bucket'] ?? ''),
            'grid_size_meters' => (int) ($validated['grid_size_meters'] ?? 300),
            'include_ranking' => (bool) ($validated['include_ranking'] ?? false),
            'include_trend' => $includeTrend,
            'include_synthetic' => (bool) ($validated['include_synthetic'] ?? true),
            'comparison_mode' => $comparisonMode,
        ];

        $job = AiJob::create([
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'type' => 'generate_heatmap',
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

    public function findJob(string $jobId): AiJob
    {
        $job = AiJob::query()
            ->where('job_id', $jobId)
            ->where('type', 'generate_heatmap')
            ->firstOrFail();

        return $job;
    }
}
