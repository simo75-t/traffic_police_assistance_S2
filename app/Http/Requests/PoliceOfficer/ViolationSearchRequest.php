<?php

namespace App\Http\Requests\PoliceOfficer;

use Illuminate\Foundation\Http\FormRequest;

class ViolationSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'plate' => ['nullable', 'string', 'max:50'],
            'from'  => ['nullable', 'date', 'before_or_equal:to'],
            'to'    => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

        
}
