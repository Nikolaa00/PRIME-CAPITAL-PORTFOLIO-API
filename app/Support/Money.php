<?php

namespace App\Support;

class Money
{
    public static function fromDecimal(string $amount): int
    {
        $normalized = trim($amount);

        if (! str_contains($normalized, '.')) {
            $normalized .= '.00';
        } elseif (strlen(explode('.', $normalized, 2)[1]) === 1) {
            $normalized .= '0';
        }

        return (int) bcmul($normalized, '100', 0);
    }

    public static function toDecimal(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = (string) abs($cents);
        $padded = str_pad($absolute, 3, '0', STR_PAD_LEFT);
        $decimal = substr($padded, 0, -2).'.'.substr($padded, -2);

        return $negative ? '-'.$decimal : $decimal;
    }
}
