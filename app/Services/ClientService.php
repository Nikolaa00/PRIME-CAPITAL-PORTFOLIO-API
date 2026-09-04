<?php

namespace App\Services;

use App\Models\Client;

class ClientService
{
    /**
     * @param  array{name: string, currency?: string}  $attributes
     */
    public function create(array $attributes): Client
    {
        return Client::create($attributes);
    }

    /**
     * @return array{id: int, name: string, currency: string}
     */
    public function toArray(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'currency' => $client->currency,
        ];
    }
}
