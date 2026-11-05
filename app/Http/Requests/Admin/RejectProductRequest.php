<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason_category' => ['required', 'in:profit_share,price,quantity,product_quality,other'],
            'admin_feedback' => ['required', 'string', 'max:1000'],
        ];
    }
}
