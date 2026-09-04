<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Client;
use App\Models\Transaction;
use App\Support\Money;

class TransactionService
{
    private LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function record(Client $client, array $validated): Transaction
    {
        $type = TransactionType::from($validated['type']);

        return match ($type) {
            TransactionType::Deposit => $this->ledgerService->deposit(
                $client,
                Money::fromDecimal((string) $validated['amount']),
            ),
            TransactionType::Withdrawal => $this->ledgerService->withdraw(
                $client,
                Money::fromDecimal((string) $validated['amount']),
            ),
            TransactionType::Buy => $this->ledgerService->buy(
                $client,
                $validated['instrument'],
                (int) $validated['quantity'],
                Money::fromDecimal((string) $validated['price']),
            ),
            TransactionType::Sell => $this->ledgerService->sell(
                $client,
                $validated['instrument'],
                (int) $validated['quantity'],
                Money::fromDecimal((string) $validated['price']),
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'client_id' => $transaction->client_id,
            'type' => $transaction->type->value,
            'amount' => Money::toDecimal($transaction->amount_cents),
            'instrument' => $transaction->instrument,
            'quantity' => $transaction->quantity,
            'price' => $transaction->price_cents === null
                ? null
                : Money::toDecimal($transaction->price_cents),
        ];
    }
}
