<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminOrderFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'pending',
                    'confirmed',
                    'processing',
                    'shipped',
                    'delivered',
                    'cancelled',
                    'returned',
                ]),
            ],

            'payment_status' => [
                'sometimes',
                'string',
                Rule::in([
                    'unpaid',
                    'paid',
                    'refunded',
                ]),
            ],
        ];
    }
}