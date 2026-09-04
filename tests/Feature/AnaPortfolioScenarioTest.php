<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnaPortfolioScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_ana_portfolio_scenario_works_end_to_end_over_api(): void
    {
        $createResponse = $this->postJson('/api/clients', [
            'name' => 'Ana',
            'currency' => 'EUR',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('name', 'Ana')
            ->assertJsonPath('currency', 'EUR');

        $clientId = $createResponse->json('id');

        $this->postJson("/api/clients/{$clientId}/transactions", [
            'type' => 'deposit',
            'amount' => '1000.00',
        ])->assertCreated()
            ->assertJsonPath('type', 'deposit')
            ->assertJsonPath('amount', '1000.00');

        $this->postJson("/api/clients/{$clientId}/transactions", [
            'type' => 'buy',
            'instrument' => 'ANA',
            'quantity' => 5,
            'price' => '100.00',
        ])->assertCreated()
            ->assertJsonPath('type', 'buy')
            ->assertJsonPath('amount', '500.00');

        $this->getJson("/api/clients/{$clientId}/balance")
            ->assertOk()
            ->assertJsonPath('cash', '500.00')
            ->assertJsonPath('currency', 'EUR');

        $this->getJson("/api/clients/{$clientId}/holdings")
            ->assertOk()
            ->assertJsonPath('holdings.ANA', 5);

        $this->getJson("/api/clients/{$clientId}")
            ->assertOk()
            ->assertJsonPath('cash', '500.00')
            ->assertJsonPath('holdings.ANA', 5);

        $this->postJson("/api/clients/{$clientId}/transactions", [
            'type' => 'buy',
            'instrument' => 'ANA',
            'quantity' => 7,
            'price' => '100.00',
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'insufficient_funds');

        $this->postJson("/api/clients/{$clientId}/transactions", [
            'type' => 'sell',
            'instrument' => 'ANA',
            'quantity' => 8,
            'price' => '120.00',
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'insufficient_holdings');

        $this->assertEquals(2, Client::query()->findOrFail($clientId)->transactions()->count());

        $this->postJson("/api/clients/{$clientId}/transactions", [
            'type' => 'sell',
            'instrument' => 'ANA',
            'quantity' => 3,
            'price' => '120.00',
        ])->assertCreated()
            ->assertJsonPath('type', 'sell')
            ->assertJsonPath('amount', '360.00');

        $this->getJson("/api/clients/{$clientId}/balance")
            ->assertOk()
            ->assertJsonPath('cash', '860.00');

        $this->getJson("/api/clients/{$clientId}/holdings")
            ->assertOk()
            ->assertJsonPath('holdings.ANA', 2);
    }
}
