<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class RejectSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_feedback' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
