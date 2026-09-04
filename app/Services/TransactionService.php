<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Client;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    private LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function paginatedLedger(Client $client, int $perPage = 15): LengthAwarePaginator
    {
        return $client->transactions()->latest()->paginate($perPage);
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
}
