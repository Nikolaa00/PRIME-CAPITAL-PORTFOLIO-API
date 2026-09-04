<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Buy = 'buy';
    case Sell = 'sell';

    public function isTrade(): bool
    {
        return in_array($this, [self::Buy, self::Sell], true);
    }

    public function cashSign(): int
    {
        return match ($this) {
            self::Deposit, self::Sell => 1,
            self::Withdrawal, self::Buy => -1,
        };
    }

    public function quantitySign(): int
    {
        return match ($this) {
            self::Buy => 1,
            self::Sell => -1,
            default => 0,
        };
    }
}
