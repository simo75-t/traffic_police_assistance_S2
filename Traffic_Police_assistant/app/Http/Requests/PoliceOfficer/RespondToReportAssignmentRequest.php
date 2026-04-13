<?php

namespace App\Http\Requests\PoliceOfficer;

use Illuminate\Foundation\Http\FormRequest;

class RespondToReportAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'in:accept,reject'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
