<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'role' => ['required', 'string', 'in:admin,super_admin'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'The username is required.',
            'username.min' => 'The username must be at least 3 characters.',
            'username.max' => 'The username must not exceed 50 characters.',
            'username.unique' => 'This username is already taken.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.max' => 'The password must not exceed 128 characters.',
            'role.required' => 'The role is required.',
            'role.in' => 'The role must be either Admin or Super Admin.',
        ];
    }
}
