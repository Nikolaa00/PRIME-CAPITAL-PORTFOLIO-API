<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(): AnonymousResourceCollection
    {
        return ClientResource::collection($this->clientService->list());
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        return (new ClientResource($client))->additional([
            'cash' => Money::toDecimal($this->clientService->cashBalanceCents($client)),
            'holdings' => $this->clientService->holdings($client),
        ]);
    }

    public function balance(Client $client): JsonResponse
    {
        return response()->json([
            'cash' => Money::toDecimal($this->clientService->cashBalanceCents($client)),
            'currency' => $client->currency,
        ]);
    }

    public function holdings(Client $client): JsonResponse
    {
        return response()->json([
            'holdings' => $this->clientService->holdings($client),
        ]);
    }
}
