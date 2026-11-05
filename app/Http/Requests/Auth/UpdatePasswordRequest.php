<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        // A seller using a temporary password should not need to know it,
        // so the current password is only required for normal changes.
        if (! $this->user()?->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }
}
