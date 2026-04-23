<?php

namespace App\Http\Requests\PoliceOfficer;

use App\Enums\RoleUserEnum;
use Illuminate\Foundation\Http\FormRequest;

class RequestSttRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === RoleUserEnum::Police_officer;
    }

    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'max:20480', 'mimetypes:audio/wav,audio/x-wav,audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,video/mp4'],
            'violation_draft_id' => ['nullable', 'integer'],
        ];
    }
}
