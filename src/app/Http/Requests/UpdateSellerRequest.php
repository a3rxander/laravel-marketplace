<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
}
