<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? $this->route('id') ?? auth()->id();

        return [
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'password' => 'sometimes|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'avatar' => 'nullable|string|max:255',
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'is_admin' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'gender.in' => 'The selected gender is invalid.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled(['first_name', 'last_name'])) {
            $this->merge([
                'name' => trim($this->first_name . ' ' . $this->last_name),
            ]);
        }
    }
}