<?php

namespace App\Http\Requests\PoliceOfficer;

use Illuminate\Foundation\Http\FormRequest;

class CompleteReportAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
