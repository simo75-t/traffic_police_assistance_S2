<?php

return [
    'exchange' => env('AI_RMQ_EXCHANGE', 'ai.exchange'),

    'queues' => [
        'ocr' => env('AI_RMQ_OCR_QUEUE', 'ai.ocr.jobs'),
        'stt' => env('AI_RMQ_STT_QUEUE', 'ai.stt.jobs'),
        'heatmap' => env('AI_RMQ_HEATMAP_QUEUE', 'ai.heatmap.jobs'),
        'heatmap_prediction' => env('AI_RMQ_HEATMAP_PREDICTION_QUEUE', 'ai.heatmap.prediction.jobs'),
        'results' => env('AI_RMQ_RESULTS_QUEUE', 'ai.results'),
    ],

    'routing_keys' => [
        'ocr' => env('AI_RMQ_OCR_ROUTING_KEY', 'job.ocr.create'),
        'stt' => env('AI_RMQ_STT_ROUTING_KEY', 'job.stt.create'),
        'heatmap' => env('AI_RMQ_HEATMAP_ROUTING_KEY', 'analytics.generate_heatmap'),
        'heatmap_prediction' => env('AI_RMQ_HEATMAP_PREDICTION_ROUTING_KEY', 'heatmap.prediction.request'),
        'results' => env('AI_RMQ_RESULTS_ROUTING_KEY', 'job.result'),
    ],

    'shared_audio_path' => env('AI_SHARED_AUDIO_PATH'),
];
