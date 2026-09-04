<?php

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientHoldingsException;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dev-only: remove when real API routes exist (Step 9+).
Route::get('/_test/domain-exception', function () {
    if (request()->query('type') === 'holdings') {
        throw new InsufficientHoldingsException('AAPL', 5, 8);
    }

    throw new InsufficientFundsException(50_000, 70_000, 'EUR');
});
