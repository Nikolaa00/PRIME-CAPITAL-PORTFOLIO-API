<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => [
                Rule::requiredIf(fn (): bool => $this->isCashMovement()),
                'prohibited_if:type,buy,sell',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:999999999.99',
            ],
            'instrument' => [
                Rule::requiredIf(fn (): bool => $this->isTrade()),
                'prohibited_if:type,deposit,withdrawal',
                'string',
                'min:1',
                'max:20',
                'regex:/^[A-Za-z0-9.\-]+$/',
            ],
            'quantity' => [
                Rule::requiredIf(fn (): bool => $this->isTrade()),
                'prohibited_if:type,deposit,withdrawal',
                'integer',
                'min:1',
            ],
            'price' => [
                Rule::requiredIf(fn (): bool => $this->isTrade()),
                'prohibited_if:type,deposit,withdrawal',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:999999999.99',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('instrument') && is_string($this->input('instrument'))) {
            $this->merge([
                'instrument' => strtoupper(trim($this->input('instrument'))),
            ]);
        }
    }

    private function isCashMovement(): bool
    {
        return in_array($this->input('type'), [
            TransactionType::Deposit->value,
            TransactionType::Withdrawal->value,
        ], true);
    }

    private function isTrade(): bool
    {
        return in_array($this->input('type'), [
            TransactionType::Buy->value,
            TransactionType::Sell->value,
        ], true);
    }
}
