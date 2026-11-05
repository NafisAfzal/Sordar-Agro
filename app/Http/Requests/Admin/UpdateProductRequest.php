<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'is_fish' => ['nullable', 'boolean'],
            'min_tank_size_litres' => ['nullable', 'integer', 'min:0'],
            'temperament' => ['nullable', 'in:peaceful,semi-aggressive,aggressive'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'in:small,medium,large,standard'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.size_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
