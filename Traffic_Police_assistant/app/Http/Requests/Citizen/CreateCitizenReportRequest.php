<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class CreateCitizenReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_phone' => ['nullable', 'string', 'max:50'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'address' => ['nullable', 'string', 'max:255'],
            'street_name' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
