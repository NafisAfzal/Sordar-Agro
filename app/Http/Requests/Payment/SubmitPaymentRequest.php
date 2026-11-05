<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A TrxID may only be used once across all orders. Normalise before
     * validation so the same code cannot be registered twice by casing.
     */
    protected function prepareForValidation(): void
    {
        $transactionId = $this->input('transaction_id');

        if (is_string($transactionId)) {
            $this->merge(['transaction_id' => strtoupper(trim($transactionId))]);
        }
    }

    public function rules(): array
    {
        $order = $this->route('order');

        return [
            'transaction_id' => [
                'required', 'string', 'min:6', 'max:50', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('orders', 'transaction_id')->ignore($order?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'Please enter the Transaction ID from your confirmation message.',
            'transaction_id.min' => 'That Transaction ID looks too short — please check your SMS.',
            'transaction_id.max' => 'That Transaction ID looks too long — please check your SMS.',
            'transaction_id.regex' => 'A Transaction ID may contain only letters, numbers, and hyphens.',
            'transaction_id.unique' => 'This Transaction ID has already been used for another order.',
        ];
    }
}
