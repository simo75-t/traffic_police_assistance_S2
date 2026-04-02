<?php

namespace App\Http\Requests\PoliceManager;

use App\Enums\AppealStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppealStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(AppealStatus::values())],
        ];
    }
}

