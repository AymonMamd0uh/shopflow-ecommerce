<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for checkout data.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => [
                'bail',
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'customer_phone' => [
                'bail',
                'required',
                'string',
                'min:7',
                'max:30',
            ],

            'shipping_city' => [
                'bail',
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'shipping_address' => [
                'bail',
                'required',
                'string',
                'min:5',
                'max:1000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
