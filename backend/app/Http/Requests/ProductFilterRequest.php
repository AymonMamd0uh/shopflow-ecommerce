<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for product filters.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],

            'min_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'max_price' => [
                'sometimes',
                'numeric',
                'min:0',
                'gte:min_price',
            ],

            'sort_by' => [
                'sometimes',
                'string',
                'in:name,price,created_at',
            ],

            'sort_direction' => [
                'sometimes',
                'string',
                'in:asc,desc',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max_price.gte' => 'The maximum price must be greater than or equal to the minimum price.',
            'per_page.max' => 'The maximum number of products per page is 100.',
        ];
    }
}
