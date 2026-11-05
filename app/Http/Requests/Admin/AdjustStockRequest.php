<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Signed delta: e.g. +10 new stock, -2 if two fish died.
            'delta' => ['required', 'integer'],
        ];
    }
}
