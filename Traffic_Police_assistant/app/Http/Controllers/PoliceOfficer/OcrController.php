<?php

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Requests\PoliceOfficer\RequestPlateOcrRequest;
use App\Http\Services\PoliceOfficer\RabbitPublisher;
use App\Models\AiJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class OcrController extends Controller
{
    public function requestPlateOcr(RequestPlateOcrRequest $request, RabbitPublisher $publisher): JsonResponse
    {
        $validated = $request->validated();
        $image = $request->file('image');

        logger()->info('OCR request received', [
            'user_id' => auth()->id(),
            'has_image' => $request->hasFile('image'),
            'original_name' => $image?->getClientOriginalName(),
            'mime' => $image?->getMimeType(),
            'size' => $image?->getSize(),
        ]);

        $path = $image->move(
            public_path('uploads/plates'),
            uniqid() . '.' . $image->getClientOriginalExtension()
        );

        $absolutePath = $path->getPathname();
        $imageUrl = asset('uploads/plates/' . basename($absolutePath));
        $jobId = (string) Str::uuid();
        $corrId = (string) Str::uuid();

        $payload = [
            'local_image_path' => $absolutePath,
            'image_url' => $imageUrl,
            'violation_draft_id' => $validated['violation_draft_id'] ?? null,
        ];

        AiJob::create([
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'type' => 'plate_ocr',
            'status' => 'queued',
            'requested_by' => auth()->id(),
            'violation_draft_id' => $validated['violation_draft_id'] ?? null,
            'payload' => $payload,
        ]);

        logger()->info('OCR job queued in database', [
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'image_path' => $absolutePath,
            'image_url' => $imageUrl,
            'file_size' => @filesize($absolutePath),
        ]);

        $publisher->publish(
            config('ai_rmq.routing_keys.ocr'),
            [
                'job_id' => $jobId,
                'correlation_id' => $corrId,
                'type' => 'plate_ocr',
                'payload' => $payload,
                'reply_to' => env('AI_RMQ_RESULTS_QUEUE', 'ai.results'),
                'schema_version' => 1,
            ],
            config('ai_rmq.queues.ocr')
        );

        return response()->json([
            'status' => 'queued',
            'job_id' => $jobId,
        ]);
    }

    public function getOcrResult(string $job_id): JsonResponse
    {
        $job = AiJob::where('job_id', $job_id)->firstOrFail();

        if ($job->type !== 'plate_ocr') {
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
