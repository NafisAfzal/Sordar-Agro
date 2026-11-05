<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveCareGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'publish' => ['nullable', 'boolean'],
        ];
    }
}
