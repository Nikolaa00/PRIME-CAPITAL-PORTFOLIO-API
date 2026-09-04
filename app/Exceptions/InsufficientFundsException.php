<?php

namespace App\Exceptions;

class InsufficientFundsException extends DomainRuleException
{
    public function __construct(
        int $balanceCents,
        int $requestedCents,
        string $currency = 'EUR',
    ) {
        $balance = number_format($balanceCents / 100, 2, '.', '');
        $requested = number_format($requestedCents / 100, 2, '.', '');

        parent::__construct("Insufficient funds: balance is {$balance} {$currency}, requested {$requested} {$currency}.");
    }

    public function errorCode(): string
    {
        return 'insufficient_funds';
    }
}
