<?php

namespace App\Http\Requests\PoliceManager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ViolationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'search_type' => [
                'nullable',
                'string',
                Rule::in(['vehicle_id', 'violation_type', 'reporter', 'occurred_at']),
            ],
            'search' => ['nullable', 'string', 'max:150'],
        ];
    }
}

