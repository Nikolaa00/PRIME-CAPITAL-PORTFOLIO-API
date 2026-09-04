<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientService
{
    private PortfolioReader $portfolioReader;

    public function __construct(PortfolioReader $portfolioReader)
    {
        $this->portfolioReader = $portfolioReader;
    }

    /**
     * @param  array{name: string, currency?: string}  $attributes
     */
    public function create(array $attributes): Client
    {
        return Client::create($attributes);
    }

    /**
     * @return Collection<int, Client>
     */
    public function list(): Collection
    {
        return Client::query()->orderBy('name')->get();
    }

    public function cashBalanceCents(Client $client): int
    {
        return $this->portfolioReader->cashBalanceCents($client);
    }

    /**
     * @return array<string, int>
     */
    public function holdings(Client $client): array
    {
        return $this->portfolioReader->holdings($client);
    }
}
