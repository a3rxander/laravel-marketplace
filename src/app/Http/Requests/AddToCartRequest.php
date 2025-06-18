<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_options' => 'nullable|array',
            'product_options.color' => 'nullable|string|max:50',
            'product_options.size' => 'nullable|string|max:50',
            'product_options.variant' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.integer' => 'Quantity must be a valid number.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateProductAvailability($validator);
            $this->validateStockQuantity($validator);
        });
    }

    protected function validateProductAvailability($validator): void
    {
        $product = Product::find($this->product_id);
        
        if (!$product) {
            return;
        }

        // Check if product is active
        if ($product->status !== 'active') {
            $validator->errors()->add('product_id', 'This product is not available for purchase.');
            return;
        }

        // Check if product is in stock
        if ($product->stock_status === 'out_of_stock') {
            $validator->errors()->add('product_id', 'This product is currently out of stock.');
            return;
        }

        // Check if seller is approved
        if ($product->seller->status !== 'approved') {
            $validator->errors()->add('product_id', 'This product is not available from this seller.');
            return;
        }
    }

    protected function validateStockQuantity($validator): void
    {
        $product = Product::find($this->product_id);
        
        if (!$product || !$product->track_stock) {
            return;
        }

        if ($this->quantity > $product->stock_quantity) {
            $validator->errors()->add('quantity', 
                "Only {$product->stock_quantity} items available in stock."
            );
        }
    }

    protected function prepareForValidation(): void
    {
        // Ensure quantity is positive
        if ($this->quantity < 1) {
            $this->merge(['quantity' => 1]);
        }

        // Clean product options
        if ($this->filled('product_options')) {
            $options = array_filter($this->product_options, function ($value) {
                return !is_null($value) && $value !== '';
            });
            $this->merge(['product_options' => $options]);
        }
    }
}