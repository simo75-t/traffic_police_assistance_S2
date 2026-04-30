<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;

class HeatmapPredictionLogger
{
    public function created(string $requestId, string $status): void
    {
        $this->info('Heatmap prediction created', [
            'request_id' => $requestId,
            'status' => $status,
        ]);
    }

    public function published(string $requestId, string $correlationId): void
    {
        $this->info('Heatmap prediction job published', [
            'request_id' => $requestId,
            'correlation_id' => $correlationId,
        ]);
    }

    public function statusRequested(string $requestId): void
    {
        $this->info('Heatmap prediction status requested', [
            'request_id' => $requestId,
        ]);
    }

    public function resultReceived(string $requestId): void
    {
        $this->info('Heatmap prediction result received', [
            'request_id' => $requestId,
        ]);
    }

    public function statusChanged(string $requestId, string $status, ?string $source = null): void
    {
        $this->info('Heatmap prediction status changed', [
            'request_id' => $requestId,
            'status' => $status,
            'source' => $source,
        ]);
    }

    public function resultMissingRequestId(): void
    {
        $this->warning('Heatmap prediction result missing request_id');
    }

    public function resultNotFound(string $requestId): void
    {
        $this->warning('Heatmap prediction not found for result', [
            'request_id' => $requestId,
        ]);
    }

    public function requestFailedBeforeQueuePublish(string $message): void
    {
        $this->error('Heatmap prediction request failed before queue publish', [
            'message' => $message,
        ]);
    }

    private function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    private function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    private function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
