<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Client;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    private LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function store(StoreTransactionRequest $request, Client $client): JsonResponse
    {
        $validated = $request->validated();
        $type = TransactionType::from($validated['type']);

        $transaction = match ($type) {
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

        return response()->json($this->transactionPayload($transaction), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionPayload(Transaction $transaction): array
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
