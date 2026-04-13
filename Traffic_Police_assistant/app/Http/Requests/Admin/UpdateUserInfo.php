<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleUserEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserInfo extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
          $user = Auth::user();
        return $user && $user->role == RoleUserEnum::Admin ;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'string',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($this->route('user')),
                ],
                'is_active' => ['required', 'boolean'] ,
        ];
    }
}
