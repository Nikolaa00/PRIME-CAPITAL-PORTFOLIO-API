# Services

Put portfolio domain logic in `app/Services/` classes, not in controllers or models.

Derive cash balance and holdings by aggregating the transactions ledger in SQL; do not load all rows into PHP to sum them.

The transactions table is the single source of truth; never store cash balance or holdings as separate persisted columns.
