# Prime Capital Portfolio API

Append-only ledger API for client cash and instrument holdings. Every deposit, withdrawal, buy, and sell is stored as an immutable transaction row. Cash balance and holdings are derived from that ledger in SQL — they are never stored separately. Business rules are enforced on every write: a client cannot spend more cash than they have, and cannot sell more of an instrument than they hold.

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Laravel Sail runs the app and PostgreSQL)
- PHP 8.5+ and [Composer](https://getcomposer.org/) (needed once for `composer install` before Sail takes over)

## Local setup

```bash
git clone <your-repo-url>
cd prime-capital-portfolio-api
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

**Base URL:** `http://localhost:8080/api`

### Ports

| Variable | Default | Why |
|----------|---------|-----|
| `APP_PORT` | `8080` | Maps host port 8080 → container port 80 (nginx). Avoids conflicts when port 80 is already in use on Windows/WSL. |
| `FORWARD_DB_PORT` | `5432` | Exposes PostgreSQL on the host for GUI clients. The app connects to `pgsql` inside the Docker network. |

After seeding, verify Ana's portfolio:

```bash
curl -s http://localhost:8080/api/clients/1
# cash: "860.00", holdings: {"AAPL": 2}
```

**Manual testing:** open the `bruno/` folder in [Bruno](https://www.usebruno.com/), select the **local** environment (`http://localhost:8080`), and run the requests.

## API reference

All requests should include `Accept: application/json`. POST bodies use `Content-Type: application/json`.

### 1. Create client

```bash
curl -s -X POST http://localhost:8080/api/clients \
  -H "Content-Type: application/json" \
  -d '{"name":"Marko","currency":"EUR"}'
```

```json
{"id":4,"name":"Marko","currency":"EUR"}
```

### 2. List clients

```bash
curl -s http://localhost:8080/api/clients
```

```json
{"data":[{"id":1,"name":"Ana","currency":"EUR"},{"id":2,"name":"Bojan","currency":"EUR"},{"id":3,"name":"Cvetanka","currency":"EUR"}]}
```

### 3. Show client (cash + holdings)

```bash
curl -s http://localhost:8080/api/clients/1
```

```json
{"data":{"id":1,"name":"Ana","currency":"EUR"},"cash":"860.00","holdings":{"AAPL":2}}
```

Client fields are under `"data"`; `cash` and `holdings` are top-level siblings.

### 4. Cash balance

```bash
curl -s http://localhost:8080/api/clients/1/balance
```

```json
{"cash":"860.00","currency":"EUR"}
```

### 5. Holdings

```bash
curl -s http://localhost:8080/api/clients/1/holdings
```

```json
{"holdings":{"AAPL":2}}
```

Fully sold instruments are omitted (not returned as zero). Cvetanka (id 3) after seed:

```json
{"data":{"id":3,"name":"Cvetanka","currency":"EUR"},"cash":"1200.00","holdings":[]}
```

### 6. List transactions (paginated, newest first)

```bash
curl -s http://localhost:8080/api/clients/1/transactions
```

```json
{
    "data": [
        {
            "id": 2,
            "client_id": 1,
            "type": "buy",
            "amount": "500.00",
            "instrument": "AAPL",
            "quantity": 5,
            "price": "100.00",
            "created_at": "2026-09-04T17:53:19+00:00"
        },
        {
            "id": 3,
            "client_id": 1,
            "type": "sell",
            "amount": "360.00",
            "instrument": "AAPL",
            "quantity": 3,
            "price": "120.00",
            "created_at": "2026-09-04T17:53:19+00:00"
        },
        {
            "id": 1,
            "client_id": 1,
            "type": "deposit",
            "amount": "1000.00",
            "instrument": null,
            "quantity": null,
            "price": null,
            "created_at": "2026-09-04T17:53:19+00:00"
        }
    ],
    "links": {
        "first": "http://localhost:8080/api/clients/1/transactions?page=1",
        "last": "http://localhost:8080/api/clients/1/transactions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://localhost:8080/api/clients/1/transactions",
        "per_page": 15,
        "to": 3,
        "total": 3
    }
}
```

### 7. Record a transaction

Single endpoint for all movement types. Pass `type`: `deposit`, `withdrawal`, `buy`, or `sell`.

**Deposit** (cash movements have `instrument`, `quantity`, and `price` as `null`):

```bash
curl -s -X POST http://localhost:8080/api/clients/4/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","amount":"100.00"}'
```

```json
{"id":11,"client_id":4,"type":"deposit","amount":"100.00","instrument":null,"quantity":null,"price":null,"created_at":"2026-09-04T17:53:20+00:00"}
```

**Buy** example:

```bash
curl -s -X POST http://localhost:8080/api/clients/4/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"buy","instrument":"AAPL","quantity":1,"price":"100.00"}'
```

**Sell** and **withdrawal** use the same URL with `"type":"sell"` or `"type":"withdrawal"` and the appropriate fields.

### Rejection examples (422)

**Business rule violation** — withdrawal above balance (`LedgerService`):

```bash
curl -s -X POST http://localhost:8080/api/clients/1/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"withdrawal","amount":"9999.00"}'
```

```json
{"message":"Insufficient funds: balance is 860.00 EUR, requested 9999.00 EUR.","error":"insufficient_funds"}
```

**Validation failure** — negative amount (`FormRequest`):

```bash
curl -s -X POST http://localhost:8080/api/clients/1/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","amount":"-10.00"}'
```

```json
{"message":"The amount field must be greater than 0.","errors":{"amount":["The amount field must be greater than 0."]}}
```

| Layer | Question | HTTP 422 shape |
|-------|----------|----------------|
| **FormRequest** | Is the request well-formed? | `{ "message": "...", "errors": { "field": ["..."] } }` |
| **LedgerService** | Is the move allowed given current balance/holdings? | `{ "message": "...", "error": "insufficient_funds" }` or `"insufficient_holdings"` |

## Business rules

Two invariants apply on every write:

1. **No negative balance** — a client cannot withdraw or buy with more cash than they hold. Violations return HTTP 422 with `"error": "insufficient_funds"`. The ledger is unchanged (no partial row).
2. **No overselling** — a client cannot sell more units of an instrument than they hold. Violations return HTTP 422 with `"error": "insufficient_holdings"`. The ledger is unchanged.

Rejections are atomic: tests assert `Transaction::count()`, cash, and holdings stay the same after a failed move.

## Running tests

```bash
./vendor/bin/sail artisan test
```

23 tests cover ledger domain rules (`LedgerServiceTest`), HTTP 201/422 responses, input validation, and the full Ana scenario end-to-end. Tests use the PostgreSQL `testing` database (`DB_CONNECTION=pgsql`, `DB_DATABASE=testing` in `phpunit.xml`).

## Why this way

**Ledger as source of truth.** Cash and holdings are SQL aggregates over `transactions`. There are no balance or holdings columns to drift out of sync with the journal.

**Integer cents internally.** Amounts are stored as `amount_cents` (and `price_cents` for trades). `LedgerService` works only in integers; `Money::fromDecimal()` / `Money::toDecimal()` convert at the API boundary so floats never touch money math. I consciously left a 2-decimal assumption because the task involves one currency per client without FX; for production I would add a currency exponent map.

**Two validation layers.** Form requests reject malformed input (wrong fields, zero quantity, negative amounts) before the service runs. `LedgerService` rejects moves that are well-formed but break account rules. Different 422 shapes make it clear which layer caught the problem.

**Concurrency.** Each write runs inside `DB::transaction` with `lockForUpdate` on the client row, so two concurrent requests cannot both pass a balance check and overdraw the account.

**One transactions endpoint.** A single `POST /api/clients/{id}/transactions` accepts a `type` field instead of four separate routes. This keeps the API small; the trade-off is that route naming no longer spells out the operation — the `type` field does.

**Instrument as free-text label.** Tickers are normalized to uppercase on write. There is no instrument catalogue; the label is whatever the operator enters.

**No authentication in this task.** The assignment focuses on ledger correctness. In production I would protect write endpoints with staff authentication (e.g. Sanctum tokens).

## What I'd add with more time

- **Idempotency keys** on `POST /transactions` so network retries cannot duplicate ledger entries
- **Staff authentication** (Sanctum) for write endpoints — not role-based authorization, which is a separate concern
- **Stricter rate limits** on write endpoints
- **Cursor pagination** for clients with long transaction histories
- **Currency-aware formatting** — ISO exponent map (`Money::toDecimal($minor, $currency)`), rename `amount_cents` → `amount_minor`
- **Audit log** tying each posted movement to the staff user who recorded it
