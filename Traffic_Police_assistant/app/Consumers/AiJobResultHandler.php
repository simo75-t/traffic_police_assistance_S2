<?php

namespace App\Consumers;

use App\Models\AiJob;

class AiJobResultHandler
{
    public function handle(array $data): void
    {
        $jobId = $data['job_id'] ?? $data['request_id'] ?? null;
        if (! $jobId) {
            logger()->warning('AI result missing job identifier');

            return;
        }

        $job = AiJob::query()->where('job_id', $jobId)->first();
        if (! $job) {
            logger()->warning('AI job not found for result', ['job_id' => $jobId]);

            return;
        }

        $job->status = ($data['status'] ?? '') === 'success' ? 'success' : 'failed';
        $job->result = $data['result'] ?? null;
        $job->error = $data['error'] ?? null;
        $job->finished_at = now();
        $job->save();
    }
}
