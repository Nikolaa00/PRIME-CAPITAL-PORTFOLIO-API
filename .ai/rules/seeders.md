# Seeders

Create sample transactions only through `LedgerService`; never raw `Transaction::create()` or SQL inserts.

Use `ClientFactory` for client rows. Seed clients in a fixed order when demo IDs matter (Ana first for `id = 1`).

Use `Money::fromDecimal()` for amounts and prices passed to `LedgerService`.
