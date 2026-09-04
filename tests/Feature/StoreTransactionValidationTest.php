<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTransactionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_with_zero_quantity_returns_per_field_validation_errors(): void
    {
        $client = Client::create(['name' => 'Ana', 'currency' => 'EUR']);

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'quantity' => 0,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity', 'instrument', 'price']);
    }

    public function test_amount_is_prohibited_on_buy(): void
    {
        $client = Client::create(['name' => 'Ana', 'currency' => 'EUR']);

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'buy',
            'amount' => '100.00',
            'instrument' => 'AAPL',
            'quantity' => 1,
            'price' => '100.00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_instrument_is_prohibited_on_deposit(): void
    {
        $client = Client::create(['name' => 'Ana', 'currency' => 'EUR']);

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000.00',
            'instrument' => 'AAPL',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['instrument']);
    }

    public function test_valid_deposit_reaches_service_and_returns_created_transaction(): void
    {
        $client = Client::create(['name' => 'Ana', 'currency' => 'EUR']);

        $response = $this->postJson("/api/clients/{$client->id}/transactions", [
            'type' => 'deposit',
            'amount' => '1000.00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('type', 'deposit')
            ->assertJsonPath('amount', '1000.00');

        $this->assertDatabaseHas('transactions', [
            'client_id' => $client->id,
            'type' => 'deposit',
            'amount_cents' => 100_000,
        ]);
    }
}
