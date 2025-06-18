<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'avatar' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'is_admin' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'first_name.required' => 'The first name field is required.',
            'last_name.required' => 'The last name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'gender.in' => 'The selected gender is invalid.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->first_name . ' ' . $this->last_name,
        ]);
    }
}