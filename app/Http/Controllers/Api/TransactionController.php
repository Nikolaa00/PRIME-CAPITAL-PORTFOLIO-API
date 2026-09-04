<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Client;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Client $client): AnonymousResourceCollection
    {
        return TransactionResource::collection(
            $this->transactionService->paginatedLedger($client),
        );
    }

    public function store(StoreTransactionRequest $request, Client $client): JsonResponse
    {
        $transaction = $this->transactionService->record($client, $request->validated());

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }
}
