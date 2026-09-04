# HTTP requests and controllers

Use Form Request classes in `app/Http/Requests/` for API input validation. Prefer array rule syntax.

Normalize tickers and currency codes in `prepareForValidation()` before rules run.

Convert decimal money to integer cents in `TransactionService` with `App\Support\Money::fromDecimal()`. `LedgerService` and the ledger work only in integer cents.

Place JSON API controllers under `app/Http/Controllers/Api/`. Register routes in `routes/api.php`.

Controllers stay thin: validate via Form Requests, then delegate to `app/Services/` classes. Do not put domain logic or Money conversion in controllers.

`TransactionService` orchestrates validated input and delegates ledger writes to `LedgerService`. Do not duplicate balance or holdings checks outside `LedgerService`.

Validation failures return HTTP 422 with Laravel's `{message, errors}` shape. Domain rule failures from `LedgerService` return HTTP 422 with `{message, error}`.

Use explicit constructor injection for controller dependencies, matching `LedgerService`.
