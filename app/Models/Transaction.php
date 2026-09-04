<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'type', 'amount_cents', 'instrument', 'quantity', 'price_cents'])]
class Transaction extends Model
{
    use AppendOnly;

    public static function tradeAmountCents(int $quantity, int $priceCents): int
    {
        return $quantity * $priceCents;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount_cents' => 'integer',
            'quantity' => 'integer',
            'price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function instrument(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null ? null : strtoupper(trim($value)),
        );
    }
}
