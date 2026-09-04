# Tests

Use PHPUnit feature tests for HTTP boundaries. Call `$this->postJson('/api/...')` against API routes with `Accept: application/json` implied.

Use `RefreshDatabase` on feature tests that hit the database.

Assert validation failures with `assertUnprocessable()` and `assertJsonValidationErrors()`. Assert domain rule failures separately when testing `LedgerService` behavior (Step 13).
