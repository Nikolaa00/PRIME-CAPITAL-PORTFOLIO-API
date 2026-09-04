# Models

Use the `#[Fillable([...])]` attribute for mass assignment on Eloquent models, not a `$fillable` property.

Define casts with a `casts()` method returning an array.

Use `#[Hidden([...])]` only on models with sensitive attributes (for example passwords), not on domain models like Client or Transaction.

Ledger `Transaction` rows are append-only. Use the `AppendOnly` concern and do not update or delete transaction records.

Normalize instrument tickers to uppercase trimmed strings via an `Attribute` setter on `Transaction`.

Store monetary values as integer cents on the model (`amount_cents`, `price_cents`); convert decimals at the API boundary.
