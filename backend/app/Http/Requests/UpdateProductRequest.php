<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        $productId = $product instanceof \App\Models\Product
            ? $product->id
            : $product;

        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('is_active', true),
            ],

            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],

            'description' => ['sometimes', 'nullable', 'string'],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],

            'price' => [
                'sometimes',
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:99999999.99',
            ],

            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],

            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
