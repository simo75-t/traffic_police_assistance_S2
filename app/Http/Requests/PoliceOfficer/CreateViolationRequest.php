<?php

namespace App\Http\Requests\PoliceOfficer;

use App\Enums\RoleUserEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateViolationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user->role == RoleUserEnum::Police_officer;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
             
            'vehicle_plate'            => ['required'],
            'violation_type_id'     => ['required', 'exists:violation_types,id'],
            'violation_location_id' => ['required', 'exists:violation_locations,id'],
            'description'           => ['nullable', 'string', 'max:2000'],
            'fine_amount'           => ['required', 'numeric', 'min:0'],
            'vehicle_snapshot'      => ['required', 'array'],   
            'occurred_at'           => ['required', 'date'],
        
        ];
    }
}
