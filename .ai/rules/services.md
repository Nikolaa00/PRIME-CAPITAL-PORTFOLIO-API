# Services

Put portfolio domain logic in `app/Services/` classes, not in controllers or models.

Split responsibilities across services:

- `PortfolioReader` — read model: cash balance and holdings from SQL aggregates
- `LedgerService` — write model: append-only ledger rows, pessimistic lock, domain rule exceptions
- `TransactionService` — API orchestration: `Money::fromDecimal()` then delegate to `LedgerService`
- `ClientService` — client creation and simple response shaping

Use explicit constructor injection: declare a private typed property and assign it in the constructor body. Do not use constructor property promotion in services.

Derive cash balance and holdings by aggregating the transactions ledger in SQL; do not load all rows into PHP to sum them.

The transactions table is the single source of truth; never store cash balance or holdings as separate persisted columns.
