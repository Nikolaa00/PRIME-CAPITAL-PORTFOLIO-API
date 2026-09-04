<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientHoldingsException;
use App\Models\Client;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\Services\PortfolioReader;
use App\Support\Money;
use Database\Factories\ClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledgerService;

    private PortfolioReader $portfolioReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = $this->app->make(LedgerService::class);
        $this->portfolioReader = $this->app->make(PortfolioReader::class);
    }

    public function test_deposit_increases_cash(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));

        $this->assertCash($client, '1000.00');
    }

    public function test_withdrawal_decreases_cash(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->withdraw($client, Money::fromDecimal('400.00'));

        $this->assertCash($client, '600.00');
    }

    public function test_withdrawal_above_balance_is_rejected_and_leaves_ledger_unchanged(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('500.00'));

        $countBefore = Transaction::count();
        $cashBefore = $this->portfolioReader->cashBalanceCents($client);
        $holdingsBefore = $this->portfolioReader->holdings($client);

        try {
            $this->ledgerService->withdraw($client, Money::fromDecimal('501.00'));
            $this->fail('Expected InsufficientFundsException');
        } catch (InsufficientFundsException $e) {
            $this->assertSame('insufficient_funds', $e->errorCode());
        }

        $this->assertSame($countBefore, Transaction::count());
        $this->assertSame($cashBefore, $this->portfolioReader->cashBalanceCents($client));
        $this->assertSame($holdingsBefore, $this->portfolioReader->holdings($client));
    }

    public function test_buy_decreases_cash_and_increases_holdings(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'AAPL', 5, Money::fromDecimal('100.00'));

        $this->assertCash($client, '500.00');
        $this->assertSame(['AAPL' => 5], $this->portfolioReader->holdings($client));
    }

    public function test_buy_above_available_cash_is_rejected(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('500.00'));
        $this->ledgerService->buy($client, 'AAPL', 5, Money::fromDecimal('100.00'));

        $countBefore = Transaction::count();
        $cashBefore = $this->portfolioReader->cashBalanceCents($client);
        $holdingsBefore = $this->portfolioReader->holdings($client);

        try {
            $this->ledgerService->buy($client, 'AAPL', 1, Money::fromDecimal('100.00'));
            $this->fail('Expected InsufficientFundsException');
        } catch (InsufficientFundsException $e) {
            $this->assertSame('insufficient_funds', $e->errorCode());
        }

        $this->assertSame($countBefore, Transaction::count());
        $this->assertSame($cashBefore, $this->portfolioReader->cashBalanceCents($client));
        $this->assertSame($holdingsBefore, $this->portfolioReader->holdings($client));
    }

    public function test_sell_above_held_quantity_is_rejected_and_leaves_ledger_unchanged(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'AAPL', 5, Money::fromDecimal('100.00'));

        $countBefore = Transaction::count();
        $cashBefore = $this->portfolioReader->cashBalanceCents($client);
        $holdingsBefore = $this->portfolioReader->holdings($client);

        try {
            $this->ledgerService->sell($client, 'AAPL', 6, Money::fromDecimal('120.00'));
            $this->fail('Expected InsufficientHoldingsException');
        } catch (InsufficientHoldingsException $e) {
            $this->assertSame('insufficient_holdings', $e->errorCode());
        }

        $this->assertSame($countBefore, Transaction::count());
        $this->assertSame($cashBefore, $this->portfolioReader->cashBalanceCents($client));
        $this->assertSame($holdingsBefore, $this->portfolioReader->holdings($client));
    }

    public function test_sell_increases_cash_and_decreases_holdings(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'AAPL', 5, Money::fromDecimal('100.00'));
        $this->ledgerService->sell($client, 'AAPL', 3, Money::fromDecimal('120.00'));

        $this->assertCash($client, '860.00');
        $this->assertSame(['AAPL' => 2], $this->portfolioReader->holdings($client));
    }

    public function test_sell_at_different_price_than_buy_is_allowed(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'AAPL', 2, Money::fromDecimal('100.00'));
        $this->ledgerService->sell($client, 'AAPL', 2, Money::fromDecimal('150.00'));

        $this->assertCash($client, '1100.00');
        $this->assertSame([], $this->portfolioReader->holdings($client));
    }

    public function test_fully_sold_instrument_disappears_from_holdings(): void
    {
        $client = ClientFactory::new()->create();

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'TSLA', 4, Money::fromDecimal('100.00'));
        $this->ledgerService->sell($client, 'TSLA', 4, Money::fromDecimal('150.00'));

        $holdings = $this->portfolioReader->holdings($client);

        $this->assertArrayNotHasKey('TSLA', $holdings);
    }

    public function test_one_client_balance_is_unaffected_by_another_clients_transactions(): void
    {
        $clientA = ClientFactory::new()->create(['name' => 'Client A']);
        $clientB = ClientFactory::new()->create(['name' => 'Client B']);

        $this->ledgerService->deposit($clientA, Money::fromDecimal('1000.00'));
        $this->ledgerService->deposit($clientB, Money::fromDecimal('500.00'));
        $this->ledgerService->buy($clientB, 'MSFT', 5, Money::fromDecimal('50.00'));

        $this->assertCash($clientA, '1000.00');
        $this->assertCash($clientB, '250.00');
        $this->assertSame(['MSFT' => 5], $this->portfolioReader->holdings($clientB));
        $this->assertSame([], $this->portfolioReader->holdings($clientA));
    }

    private function assertCash(Client $client, string $expectedDecimal): void
    {
        $this->assertSame(
            $expectedDecimal,
            Money::toDecimal($this->portfolioReader->cashBalanceCents($client)),
        );
    }
}
