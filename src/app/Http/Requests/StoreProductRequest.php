<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'seller_id' => 'sometimes|integer|exists:sellers,id',
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'nullable|string|max:100|unique:products',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'track_stock' => 'nullable|boolean',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'status' => ['nullable', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'is_featured' => 'nullable|boolean',
            'is_digital' => 'nullable|boolean',
            'images' => 'nullable|array',
            'images.*' => 'string|max:255',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string|max:255',
            'attributes' => 'nullable|array',
            'variants' => 'nullable|array',
            'meta_data' => 'nullable|array',
            'meta_data.title' => 'nullable|string|max:255',
            'meta_data.description' => 'nullable|string|max:500',
            'meta_data.keywords' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required.',
            'description.required' => 'The product description is required.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'price.required' => 'The price is required.',
            'price.min' => 'The price must be greater than or equal to 0.',
            'compare_price.gt' => 'The compare price must be greater than the regular price.',
            'stock_quantity.required' => 'The stock quantity is required.',
            'stock_quantity.min' => 'The stock quantity must be greater than or equal to 0.',
            'sku.unique' => 'This SKU is already taken.',
            'slug.unique' => 'This slug is already taken.',
            'seller_id.exists' => 'The selected seller is invalid.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->filled('track_stock')) {
            $this->merge(['track_stock' => true]);
        }

        if (!$this->filled('is_featured')) {
            $this->merge(['is_featured' => false]);
        }

        if (!$this->filled('is_digital')) {
            $this->merge(['is_digital' => false]);
        }

        if (!$this->filled('min_stock_level')) {
            $this->merge(['min_stock_level' => 5]);
        }

        if (!$this->filled('status')) {
            $this->merge(['status' => 'draft']);
        }
    }
}