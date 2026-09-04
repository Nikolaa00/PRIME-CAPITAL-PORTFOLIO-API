<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/clients', [ClientController::class, 'store']);
Route::post('/clients/{client}/transactions', [TransactionController::class, 'store']);
