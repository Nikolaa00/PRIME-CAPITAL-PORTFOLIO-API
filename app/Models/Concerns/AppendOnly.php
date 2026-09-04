<?php

namespace App\Models\Concerns;

use LogicException;

trait AppendOnly
{
    protected static function bootAppendOnly(): void
    {
        static::updating(fn () => throw new LogicException('Ledger entries are append-only and cannot be modified.'));

        static::deleting(fn () => throw new LogicException('Ledger entries are append-only and cannot be deleted.'));
    }
}
