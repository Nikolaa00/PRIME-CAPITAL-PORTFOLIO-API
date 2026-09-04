<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'type' => $this->type->value,
            'amount' => Money::toDecimal($this->amount_cents),
            'instrument' => $this->instrument,
            'quantity' => $this->quantity,
            'price' => $this->price_cents === null
                ? null
                : Money::toDecimal($this->price_cents),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
