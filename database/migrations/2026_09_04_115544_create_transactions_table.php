<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->bigInteger('amount_cents');
            $table->string('instrument')->nullable();
            $table->integer('quantity')->nullable();
            $table->bigInteger('price_cents')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('instrument');
            $table->index(['client_id', 'instrument']);
        });

        DB::statement("
            ALTER TABLE transactions
            ADD CONSTRAINT transactions_amount_cents_positive
                CHECK (amount_cents > 0),
            ADD CONSTRAINT transactions_quantity_positive
                CHECK (quantity IS NULL OR quantity > 0),
            ADD CONSTRAINT transactions_price_cents_positive
                CHECK (price_cents IS NULL OR price_cents > 0),
            ADD CONSTRAINT transactions_type_valid
                CHECK (type IN ('deposit', 'withdrawal', 'buy', 'sell')),
            ADD CONSTRAINT transactions_trade_fields
                CHECK (
                    (type IN ('buy', 'sell') AND instrument IS NOT NULL AND quantity IS NOT NULL AND price_cents IS NOT NULL)
                    OR
                    (type IN ('deposit', 'withdrawal') AND instrument IS NULL AND quantity IS NULL AND price_cents IS NULL)
                ),
            ADD CONSTRAINT transactions_trade_amount_matches
                CHECK (
                    type NOT IN ('buy', 'sell')
                    OR amount_cents = quantity * price_cents
                )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
