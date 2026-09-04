<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Database\Factories\ClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_buy_returns_201_and_persists_ledger_row(): void
    {
        $client = ClientFactory::new()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000.00',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => '100.00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'buy')
            ->assertJsonPath('amount', '500.00')
            ->assertJsonPath('instrument', 'AAPL')
            ->assertJsonPath('quantity', 5)
            ->assertJsonPath('price', '100.00');

        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'buy',
            'amount_cents' => 50_000,
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price_cents' => 10_000,
        ]);
    }

    public function test_post_sell_returns_201_and_persists_ledger_row(): void
    {
        $client = ClientFactory::new()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'instrument' => 'AAPL',
            'quantity' => 5,
            'price' => '100.00',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'sell',
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price' => '120.00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'sell')
            ->assertJsonPath('amount', '360.00')
            ->assertJsonPath('instrument', 'AAPL')
            ->assertJsonPath('quantity', 3)
            ->assertJsonPath('price', '120.00');

        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'sell',
            'amount_cents' => 36_000,
            'instrument' => 'AAPL',
            'quantity' => 3,
            'price_cents' => 12_000,
        ]);
    }

    public function test_post_withdrawal_returns_201_and_persists_ledger_row(): void
    {
        $client = ClientFactory::new()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000.00',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'withdrawal',
            'amount' => '400.00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'withdrawal')
            ->assertJsonPath('amount', '400.00')
            ->assertJsonPath('instrument', null)
            ->assertJsonPath('quantity', null)
            ->assertJsonPath('price', null);

        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'withdrawal',
            'amount_cents' => 40_000,
        ]);
    }

    public function test_withdrawal_above_balance_returns_422_with_insufficient_funds_error(): void
    {
        $client = ClientFactory::new()->create();

        $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '500.00',
        ])->assertCreated();

        $countBefore = Transaction::count();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'withdrawal',
            'amount' => '501.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error', 'insufficient_funds');

        $this->assertSame($countBefore, Transaction::count());
    }

    public function test_negative_amount_returns_validation_errors(): void
    {
        $client = ClientFactory::new()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '-10.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_buy_without_instrument_returns_validation_errors(): void
    {
        $client = ClientFactory::new()->create();

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'quantity' => 1,
            'price' => '100.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['instrument']);
    }
}
