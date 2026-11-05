<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentStatusRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for updating payment status.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_status' => [
                'bail',
                'required',
                'string',
                Rule::in([
                    'paid',
                    'refunded',
                ]),
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
            'payment_status.required' => 'A payment status is required.',
            'payment_status.in' => 'The selected payment status is invalid.',
        ];
    }
}
