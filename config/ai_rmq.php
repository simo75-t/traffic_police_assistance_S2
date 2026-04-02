<?php

return [
    'exchange' => env('AI_RMQ_EXCHANGE', 'ai.exchange'),

    'queues' => [
        'stt' => env('AI_RMQ_STT_QUEUE', 'ai.stt.jobs'),
    ],

    'routing_keys' => [
        'stt' => env('AI_RMQ_STT_ROUTING_KEY', 'job.stt.create'),
        'results' => env('AI_RMQ_RESULTS_ROUTING_KEY', 'job.result'),
    ],

    'shared_audio_path' => env('AI_SHARED_AUDIO_PATH'),
];
