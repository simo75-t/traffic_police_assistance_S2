<?php

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Services\PoliceOfficer\RabbitPublisher;
use App\Models\AiJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OcrController extends Controller
{
    public function requestPlateOcr(Request $request, RabbitPublisher $publisher)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'violation_draft_id' => 'nullable|integer',
        ]);

        logger()->info('OCR request received', [
            'user_id' => auth()->id(),
            'has_image' => $request->hasFile('image'),
            'original_name' => $request->file('image')?->getClientOriginalName(),
            'mime' => $request->file('image')?->getMimeType(),
            'size' => $request->file('image')?->getSize(),
        ]);

        $path = $request->file('image')->move(
            public_path('uploads/plates'),
            uniqid() . '.' . $request->file('image')->getClientOriginalExtension()
        );

        $absolutePath = $path->getPathname();
        $imageUrl = asset('uploads/plates/' . basename($absolutePath));

        $jobId = (string) Str::uuid();
        $corrId = (string) Str::uuid();

        $payload = [
            'local_image_path' => $absolutePath,
            'image_url' => $imageUrl,
            'violation_draft_id' => $request->input('violation_draft_id'),
        ];

        AiJob::create([
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'type' => 'plate_ocr',
            'status' => 'queued',
            'requested_by' => auth()->id(),
            'violation_draft_id' => $request->input('violation_draft_id'),
            'payload' => $payload,
        ]);

        logger()->info('OCR job queued in database', [
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'image_path' => $absolutePath,
            'image_url' => $imageUrl,
            'file_size' => @filesize($absolutePath),
        ]);

        $message = [
            'job_id' => $jobId,
            'correlation_id' => $corrId,
            'type' => 'plate_ocr',
            'payload' => $payload,
            'reply_to' => env('AI_RMQ_RESULTS_QUEUE', 'ai.results'),
            'schema_version' => 1,
        ];

        $routingKey = config('ai_rmq.routing_keys.ocr');
        $queueName = config('ai_rmq.queues.ocr');

        $publisher->publish($routingKey, $message, $queueName);

        logger()->info('OCR message published', [
            'job_id' => $jobId,
            'queue' => $queueName,
            'routing_key' => $routingKey,
        ]);

        return response()->json([
            'status' => 'queued',
            'job_id' => $jobId,
        ]);
    }

    public function getOcrResult(string $job_id)
    {
        $job = AiJob::where('job_id', $job_id)->firstOrFail();

        return response()->json([
            'job_id' => $job->job_id,
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }
}
