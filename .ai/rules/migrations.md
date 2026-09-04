# Migrations

Use `foreignId(...)->constrained()->cascadeOnDelete()` for referential integrity unless a different delete behavior is required.

Store money as `bigInteger` cents columns (`amount_cents`, `price_cents`), not floats.

Add PostgreSQL `CHECK` constraints with `DB::statement()` for ledger invariants that must hold even on direct database writes.

Add indexes for real query patterns (for example `type`, `instrument`, and composite `(client_id, instrument)` on transactions).
