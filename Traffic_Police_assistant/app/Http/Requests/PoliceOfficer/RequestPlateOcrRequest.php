<?php

namespace App\Http\Requests\PoliceOfficer;

use App\Enums\RoleUserEnum;
use Illuminate\Foundation\Http\FormRequest;

class RequestPlateOcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === RoleUserEnum::Police_officer;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:10240'],
            'violation_draft_id' => ['nullable', 'integer'],
        ];
    }
}
