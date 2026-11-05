<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:processing,shipped,delivered,cancelled'],
            'courier' => ['nullable', 'in:pathao,steadfast'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
        ];
    }
}
