<?php

namespace App\Http\Requests\PoliceOfficer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLiveLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'availability_status' => ['nullable', 'in:available,offline,responding'],
        ];
    }
}
