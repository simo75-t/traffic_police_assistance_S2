<?php

namespace App\Http\Requests\PoliceManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ViolationHeatmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'city' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'violation_type_id' => ['nullable', 'integer', 'exists:violation_types,id'],
            'time_bucket' => ['nullable', 'string', Rule::in(['', 'morning', 'afternoon', 'evening', 'night'])],
            'grid_size_meters' => ['nullable', 'integer', 'min:50', 'max:2000'],
            'include_ranking' => ['nullable', 'boolean'],
            'include_trend' => ['nullable', 'boolean'],
            'include_synthetic' => ['nullable', 'boolean'],
            'comparison_mode' => ['nullable', 'string', Rule::in(['', 'week_over_week', 'month_over_month'])],
        ];
    }
}
