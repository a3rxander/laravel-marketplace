<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
         return $this->user()->can('create', Seller::class);
    }

    public function rules(): array
    {
        return [
            'business_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:sellers',
            'description' => 'nullable|string|max:1000',
            'business_email' => 'nullable|email|max:255',
            'business_phone' => 'nullable|string|max:20',
            'business_registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'banner' => 'nullable|string|max:255',
            'business_hours' => 'nullable|array',
            'business_hours.*.day' => 'required_with:business_hours|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'business_hours.*.open' => 'required_with:business_hours|date_format:H:i',
            'business_hours.*.close' => 'required_with:business_hours|date_format:H:i|after:business_hours.*.open',
            'business_hours.*.is_closed' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required' => 'The business name is required.',
            'business_name.max' => 'The business name may not be greater than 255 characters.',
            'business_email.email' => 'Please enter a valid business email address.',
            'slug.unique' => 'This business slug is already taken.',
            'business_hours.*.day.in' => 'Invalid day of the week.',
            'business_hours.*.open.date_format' => 'The opening time must be in HH:MM format.',
            'business_hours.*.close.date_format' => 'The closing time must be in HH:MM format.',
            'business_hours.*.close.after' => 'The closing time must be after the opening time.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clean up business hours data
        if ($this->filled('business_hours')) {
            $businessHours = collect($this->business_hours)->map(function ($hours) {
                return [
                    'day' => strtolower($hours['day'] ?? ''),
                    'open' => $hours['open'] ?? null,
                    'close' => $hours['close'] ?? null,
                    'is_closed' => $hours['is_closed'] ?? false,
                ];
            })->toArray();

            $this->merge(['business_hours' => $businessHours]);
        }
    }
}