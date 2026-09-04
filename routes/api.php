<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// Create a new client
Route::post('/clients', [ClientController::class, 'store']);

// List all clients
Route::get('/clients', [ClientController::class, 'index']);

// Show a specific client
Route::get('/clients/{client}', [ClientController::class, 'show']);

// Get client balance
Route::get('/clients/{client}/balance', [ClientController::class, 'balance']);

// Get client holdings
Route::get('/clients/{client}/holdings', [ClientController::class, 'holdings']);

// List client transactions
Route::get('/clients/{client}/transactions', [TransactionController::class, 'index']);

// Create a new transaction for a client
Route::post('/clients/{client}/transactions', [TransactionController::class, 'store']);
