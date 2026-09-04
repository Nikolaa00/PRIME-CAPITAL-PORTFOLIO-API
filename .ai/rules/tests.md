# Tests

Use PHPUnit feature tests for HTTP boundaries. Call `$this->postJson('/api/...')` against API routes with `Accept: application/json` implied.

Use `RefreshDatabase` on feature tests and service-level unit tests that hit the database.

Use `ClientFactory` for test clients.

## Ledger service tests

Cover domain rules in `tests/Unit/LedgerServiceTest.php`. Extend `Tests\TestCase`, resolve `LedgerService` and `PortfolioReader` from the container, and use `Money::fromDecimal()` for inputs.

## Rejection tests

When a move must be rejected, assert the ledger is unchanged:

- `Transaction::count()` unchanged
- cash balance unchanged
- holdings unchanged

At the HTTP boundary use `assertUnprocessable()` with `assertJsonPath('error', 'insufficient_funds')` or `insufficient_holdings`.

## Validation tests

Assert validation failures with `assertUnprocessable()` and `assertJsonValidationErrors()`. Assert domain rule failures separately from shape validation.

PHPUnit uses `DB_CONNECTION=pgsql` and `DB_DATABASE=testing` in `phpunit.xml`.
