# HTTP requests and controllers

Use Form Request classes in `app/Http/Requests/` for API input validation. Prefer array rule syntax.

Normalize tickers and currency codes in `prepareForValidation()` before rules run.

Convert decimal money to integer cents at the API boundary with `App\Support\Money::fromDecimal()`. Services and the ledger work only in integer cents.

Controllers delegate business rules to `LedgerService`. Do not duplicate balance or holdings checks in controllers or form requests.

Validation failures return HTTP 422 with Laravel's `{message, errors}` shape. Domain rule failures from `LedgerService` return HTTP 422 with `{message, error}`.

Use explicit constructor injection for controller dependencies, matching `LedgerService`.
