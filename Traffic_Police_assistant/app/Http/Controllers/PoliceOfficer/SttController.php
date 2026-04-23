<?php

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceOfficer\RequestSttRequest;
use App\Http\Services\PoliceOfficer\RabbitPublisher;
use App\Models\AiJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SttController extends Controller
{
    public function requestStt(RequestSttRequest $request, RabbitPublisher $publisher): JsonResponse
    {
        $validated = $request->validated();
        $audio = $request->file('audio');
        $filename = uniqid('stt_') . '.' . ($audio->getClientOriginalExtension() ?: 'wav');

        $audio->move(public_path('uploads/audio'), $filename);
        $absolutePath = public_path('uploads/audio/' . $filename);
        $audioUrl = asset('uploads/audio/' . $filename);
        $jobId = (string) Str::uuid();
        $corrId = (string) Str::uuid();

        $payload = [
            'audio_url' => $audioUrl,
            'local_audio_path' => $absolutePath,
            'violation_draft_id' => $validated['violation_draft_id'] ?? null,
        ];

        AiJob::create([
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'type' => 'stt',
            'status' => 'queued',
            'requested_by' => auth()->id(),
            'violation_draft_id' => $validated['violation_draft_id'] ?? null,
            'payload' => $payload,
        ]);

        logger()->info('STT payload', [
            'job_id' => $jobId,
            'audio_url' => $audioUrl,
            'file_path' => $absolutePath,
            'file_size' => @filesize($absolutePath),
        ]);

        $publisher->publish(
            config('ai_rmq.routing_keys.stt'),
            [
                'job_id' => $jobId,
                'correlation_id' => $corrId,
                'type' => 'stt',
                'payload' => $payload,
                'schema_version' => 1,
            ],
            config('ai_rmq.queues.stt')
        );

        return response()->json([
            'status' => 'queued',
            'job_id' => $jobId,
        ]);
    }

    public function getSttResult(string $job_id): JsonResponse
    {
        $job = AiJob::where('job_id', $job_id)->firstOrFail();

        if ($job->type !== 'stt') {
            abort(404);
        }

        return response()->json([
            'job_id' => $job->job_id,
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }
}
