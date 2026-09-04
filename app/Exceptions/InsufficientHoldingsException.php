<?php

namespace App\Exceptions;

class InsufficientHoldingsException extends DomainRuleException
{
    public function __construct(
        string $instrument,
        int $held,
        int $requested,
    ) {
        $instrument = strtoupper(trim($instrument));

        parent::__construct("Insufficient holdings: client holds {$held} {$instrument}, tried to sell {$requested}.");
    }

    public function errorCode(): string
    {
        return 'insufficient_holdings';
    }
}
