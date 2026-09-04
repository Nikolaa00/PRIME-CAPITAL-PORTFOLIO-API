# API resources

Use `JsonResource` classes in `app/Http/Resources/` for API JSON responses.

Set `public static $wrap = null` on resources so single-resource responses stay flat and match existing API tests.

Expose money as decimal strings via `Money::toDecimal()` in resources. Never expose raw `*_cents` fields in API JSON.

Use `ClientResource` for clients; include `cash` and `holdings` via `additional()` on the show endpoint only.

Use `TransactionResource` for ledger entries, including paginated collections on `GET /clients/{client}/transactions`.
