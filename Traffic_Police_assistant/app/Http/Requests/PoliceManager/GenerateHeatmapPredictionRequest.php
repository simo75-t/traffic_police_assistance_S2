<?php

namespace App\Http\Requests\PoliceManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateHeatmapPredictionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'heatmap_summary' => ['required', 'array'],
            'heatmap_summary.city' => ['required', 'string', 'max:120'],
            'heatmap_summary.from_date' => ['required', 'date'],
            'heatmap_summary.to_date' => ['required', 'date', 'after_or_equal:heatmap_summary.from_date'],
            'heatmap_summary.violation_type' => ['nullable', 'string', 'max:120'],
            'heatmap_summary.time_bucket' => ['nullable', 'string', Rule::in(['all_day', 'morning', 'afternoon', 'evening', 'night'])],
            'heatmap_summary.hotspots' => ['required', 'array', 'min:1'],
            'heatmap_summary.hotspots.*.area_name' => ['required', 'string', 'max:120'],
            'heatmap_summary.hotspots.*.density_score' => ['required', 'numeric', 'min:0'],
            'heatmap_summary.hotspots.*.rank' => ['required', 'integer', 'min:1'],
            'heatmap_summary.hotspots.*.trend' => ['required', 'string', Rule::in(['increasing', 'stable', 'decreasing', 'up', 'down'])],
            'heatmap_summary.hotspots.*.percentage_change' => ['required', 'numeric'],
            'heatmap_summary.hotspots.*.dominant_violation_type' => ['required', 'string', 'max:120'],
            'heatmap_summary.hotspots.*.dominant_time_bucket' => ['required', 'string', Rule::in(['all_day', 'morning', 'afternoon', 'evening', 'night'])],
            'heatmap_summary.hotspots.*.recent_count' => ['nullable', 'integer', 'min:0'],
            'heatmap_summary.hotspots.*.previous_count' => ['nullable', 'integer', 'min:0'],
            'heatmap_summary.hotspots.*.moving_average_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
