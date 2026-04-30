<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatmapPrediction extends Model
{
    protected $table = 'heatmap_predictions';

    protected $fillable = [
        'request_id',
        'correlation_id',
        'status',
        'source',
        'payload',
        'result',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
