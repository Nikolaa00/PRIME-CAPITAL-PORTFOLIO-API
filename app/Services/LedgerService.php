<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientHoldingsException;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function __construct(
        private PortfolioReader $portfolioReader,
    ) {}

    public function deposit(Client $client, int $amountCents): Transaction
    {
        return $this->withLockedClient($client, function (Client $lockedClient) use ($amountCents): Transaction {
            return $lockedClient->transactions()->create([
                'type' => TransactionType::Deposit,
                'amount_cents' => $amountCents,
            ]);
        });
    }

    public function withdraw(Client $client, int $amountCents): Transaction
    {
        return $this->withLockedClient($client, function (Client $lockedClient) use ($amountCents): Transaction {
            $balanceCents = $this->portfolioReader->cashBalanceCents($lockedClient);

            if ($balanceCents < $amountCents) {
                throw new InsufficientFundsException($balanceCents, $amountCents, $lockedClient->currency);
            }

            return $lockedClient->transactions()->create([
                'type' => TransactionType::Withdrawal,
                'amount_cents' => $amountCents,
            ]);
        });
    }

    public function buy(Client $client, string $instrument, int $quantity, int $priceCents): Transaction
    {
        return $this->withLockedClient($client, function (Client $lockedClient) use ($instrument, $quantity, $priceCents): Transaction {
            $amountCents = Transaction::tradeAmountCents($quantity, $priceCents);
            $balanceCents = $this->portfolioReader->cashBalanceCents($lockedClient);

            if ($balanceCents < $amountCents) {
                throw new InsufficientFundsException($balanceCents, $amountCents, $lockedClient->currency);
            }

            return $lockedClient->transactions()->create([
                'type' => TransactionType::Buy,
                'amount_cents' => $amountCents,
                'instrument' => $instrument,
                'quantity' => $quantity,
                'price_cents' => $priceCents,
            ]);
        });
    }

    public function sell(Client $client, string $instrument, int $quantity, int $priceCents): Transaction
    {
        return $this->withLockedClient($client, function (Client $lockedClient) use ($instrument, $quantity, $priceCents): Transaction {
            $held = $this->portfolioReader->quantityHeld($lockedClient, $instrument);

            if ($held < $quantity) {
                throw new InsufficientHoldingsException($instrument, $held, $quantity);
            }

            return $lockedClient->transactions()->create([
                'type' => TransactionType::Sell,
                'amount_cents' => Transaction::tradeAmountCents($quantity, $priceCents),
                'instrument' => $instrument,
                'quantity' => $quantity,
                'price_cents' => $priceCents,
            ]);
        });
    }

    private function withLockedClient(Client $client, callable $callback): Transaction
    {
        return DB::transaction(function () use ($client, $callback): Transaction {
            $lockedClient = Client::query()
                ->whereKey($client->id)
                ->lockForUpdate()
                ->firstOrFail();

            return $callback($lockedClient);
        });
    }
}
