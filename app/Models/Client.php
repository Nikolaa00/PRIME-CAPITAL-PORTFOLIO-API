<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'currency'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'EUR',
    ];

    /**
     * @return array<int, string>
     */
    protected function casts(): array
    {
        return [
            'currency' => 'string',
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
