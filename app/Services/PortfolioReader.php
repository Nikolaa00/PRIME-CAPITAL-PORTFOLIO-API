<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Transaction;

class PortfolioReader
{
    public function cashBalanceCents(Client $client): int
    {
        $balance = Transaction::query()
            ->where('client_id', $client->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('deposit', 'sell') THEN amount_cents ELSE -amount_cents END), 0) AS balance")
            ->value('balance');

        return (int) $balance;
    }

    /**
     * @return array<string, int> Instrument ticker => quantity held. Fully sold instruments are omitted, not returned as zero.
     */
    public function holdings(Client $client): array
    {
        $rows = Transaction::query()
            ->where('client_id', $client->id)
            ->whereNotNull('instrument')
            ->selectRaw("instrument, SUM(CASE WHEN type = 'buy' THEN quantity WHEN type = 'sell' THEN -quantity ELSE 0 END) AS quantity")
            ->groupBy('instrument')
            ->havingRaw("SUM(CASE WHEN type = 'buy' THEN quantity WHEN type = 'sell' THEN -quantity ELSE 0 END) > 0")
            ->pluck('quantity', 'instrument');

        return $rows->map(fn ($quantity) => (int) $quantity)->all();
    }

    public function quantityHeld(Client $client, string $instrument): int
    {
        $normalizedInstrument = strtoupper(trim($instrument));

        if ($normalizedInstrument === '') {
            return 0;
        }

        $quantity = Transaction::query()
            ->where('client_id', $client->id)
            ->where('instrument', $normalizedInstrument)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'buy' THEN quantity WHEN type = 'sell' THEN -quantity ELSE 0 END), 0) AS quantity")
            ->value('quantity');

        return max(0, (int) $quantity);
    }
}
