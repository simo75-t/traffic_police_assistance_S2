<?php

namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Services\PoliceOfficer\RabbitPublisher;
use App\Models\AiJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SttController extends Controller
{
    public function requestStt(Request $request, RabbitPublisher $publisher)
{
    $request->validate([
        'audio' => 'required|file|max:20480|mimetypes:audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,video/mp4',
        'violation_draft_id' => 'nullable|integer',
    ]);

    // 1️⃣ حفظ الصوت
    $file = $request->file('audio');
    $filename = uniqid('stt_') . '.' . ($file->getClientOriginalExtension() ?: 'wav');

    $file->move(public_path('uploads/audio'), $filename);
    $absolutePath = public_path('uploads/audio/' . $filename);

    // 2️⃣ توليد audio_url
    $audioUrl = asset('uploads/audio/' . $filename);
    // مثال:
    // http://your-laravel-domain/uploads/audio/stt_xxx.ogg

    // 3️⃣ Job
    $jobId = (string) Str::uuid();
    $corrId = (string) Str::uuid();

    $payload = [
        'audio_url' => $audioUrl,
        'local_audio_path' => $absolutePath,
        'violation_draft_id' => $request->input('violation_draft_id'),
    ];

    AiJob::create([
        'job_id' => $jobId,
        'correlation_id' => $corrId,
        'type' => 'stt',
        'status' => 'queued',
        'requested_by' => auth()->id(),
        'violation_draft_id' => $request->input('violation_draft_id'),
        'payload' => $payload,
    ]);
logger()->info("STT payload", [
  "job_id" => $jobId,
  "audio_url" => $audioUrl,
  "file_path" => $absolutePath,
  "file_size" => filesize($absolutePath),
]);

    // 4️⃣ RabbitMQ
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

    public function getSttResult(string $job_id)
    {
        $job = AiJob::where('job_id', $job_id)->firstOrFail();

        if ($job->type !== 'stt') abort(404);

        return response()->json([
            'job_id' => $job->job_id,
            'status' => $job->status,
            'result' => $job->result,
            'error' => $job->error,
        ]);
    }
}
