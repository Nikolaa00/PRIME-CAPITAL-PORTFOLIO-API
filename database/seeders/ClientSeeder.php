<?php

namespace Database\Seeders;

use App\Services\LedgerService;
use App\Support\Money;
use Database\Factories\ClientFactory;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    private LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function run(): void
    {
        $this->seedAna();
        $this->seedBojan();
        $this->seedCvetanka();
    }

    private function seedAna(): void
    {
        $client = ClientFactory::new()->create(['name' => 'Ana']);

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'AAPL', 5, Money::fromDecimal('100.00'));
        $this->ledgerService->sell($client, 'AAPL', 3, Money::fromDecimal('120.00'));
    }

    private function seedBojan(): void
    {
        $client = ClientFactory::new()->create(['name' => 'Bojan']);

        $this->ledgerService->deposit($client, Money::fromDecimal('5000.00'));
        $this->ledgerService->buy($client, 'MSFT', 3, Money::fromDecimal('200.00'));
        $this->ledgerService->buy($client, 'MSFT', 2, Money::fromDecimal('250.00'));
        $this->ledgerService->buy($client, 'GOOG', 10, Money::fromDecimal('50.00'));
    }

    private function seedCvetanka(): void
    {
        $client = ClientFactory::new()->create(['name' => 'Cvetanka']);

        $this->ledgerService->deposit($client, Money::fromDecimal('1000.00'));
        $this->ledgerService->buy($client, 'TSLA', 4, Money::fromDecimal('100.00'));
        $this->ledgerService->sell($client, 'TSLA', 4, Money::fromDecimal('150.00'));
    }
}
