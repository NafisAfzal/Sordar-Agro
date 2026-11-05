<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

abstract class ProductRequest extends FormRequest
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
            'profit_share_amount' => ['required', 'numeric', 'gt:0'],

            // Fish: three size rows. Non-fish: a single "standard" row. The
            // form posts arrays keyed by size; we validate the prices/stock.
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['required', 'in:small,medium,large,standard'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.size_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
