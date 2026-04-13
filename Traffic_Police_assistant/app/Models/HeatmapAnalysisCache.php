<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatmapAnalysisCache extends Model
{
    public $timestamps = false;

    protected $table = 'heatmap_analysis_cache';

    protected $fillable = [
        'cache_key',
        'violation_type_id',
        'time_bucket',
        'start_date',
        'end_date',
        'grid_size',
        'generated_at',
        'expires_at',
        'result_json',
        'created_at',
    ];
}
