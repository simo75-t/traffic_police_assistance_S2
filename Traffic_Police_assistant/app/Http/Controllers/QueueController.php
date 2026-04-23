<?php

namespace App\Http\Controllers;

use App\Http\Services\PoliceOfficer\RabbitPublisher;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        private readonly RabbitPublisher $publisher,
    ) {
    }

    public function sendMessage()
    {
        $payload = [
            'job_type' => 'test_queue',
            'job_id' => (string) \Illuminate\Support\Str::uuid(),
            'source' => 'laravel',
            'message' => 'test from controller',
        ];

        try {
            $this->publisher->publish(
                config('ai_rmq.routing_keys.heatmap'),
                $payload,
                config('ai_rmq.queues.heatmap')
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to publish queue message',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Message dispatched to queue',
            'queue' => config('ai_rmq.queues.heatmap'),
        ]);
    }
}
