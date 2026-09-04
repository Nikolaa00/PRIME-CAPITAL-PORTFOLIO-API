# Prime Capital Portfolio API

Изберете јазик / Choose language:
- [Македонски (Macedonian)](#македонски-macedonian)
- [English](#english)

---

## Македонски (Macedonian)

API базирано на додатен дневник (append-only ledger) за готовина и сопственост на инструменти на клиенти. Секој депозит, повлекување, купување и продавање се зачувува како неменлив (immutable) ред во трансакциите. Состојбата на готовина и портфолиото се изведуваат директно од тој дневник преку SQL агрегации — тие никогаш не се зачувуваат како посебни колони во базата. Бизнис правилата се применуваат при секое запишување: клиентот не може да потроши повеќе готовина отколку што има, и не може да продаде повеќе парчиња од некој инструмент отколку што поседува.

## Барања

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Laravel Sail ги стартува апликацијата и PostgreSQL)
- PHP 8.5+ и [Composer](https://getcomposer.org/) (потребни се само еднаш за почетниот `composer install` пред да преземе Sail)

## Локално подигнување

```bash
git clone <your-repo-url>
cd prime-capital-portfolio-api
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

**Основна URL адреса:** `http://localhost:8080/api`

### Портови

| Променлива | Стандардна вредност | Зошто? |
|------------|---------------------|--------|
| `APP_PORT` | `8080` | Го мапира хост портот 8080 во контејнерскиот порт 80 (nginx). Спречува конфликти доколку портот 80 е веќе зафатен на Windows/WSL. |
| `FORWARD_DB_PORT` | `5432` | Го изложува PostgreSQL на хостот за GUI клиенти. Апликацијата се поврзува со `pgsql` внатре во Docker мрежата. |

По завршување на seeding, потврдете го портфолиото на Ана:

```bash
curl -s http://localhost:8080/api/clients/1
# готовина: "860.00", сопственост: {"AAPL": 2}
```

**Мануелно тестирање:** отворете го фолдерот `bruno/` во [Bruno](https://www.usebruno.com/), изберете ја околината **local** (`http://localhost:8080`) и извршете ги барањата.

## API референца

Сите барања треба да го вклучуваат заглавието `Accept: application/json`. Телото на POST барањата користи `Content-Type: application/json`.

### 1. Креирај клиент

```bash
curl -s -X POST http://localhost:8080/api/clients \
  -H "Content-Type: application/json" \
  -d '{"name":"Marko","currency":"EUR"}'
```

```json
{"id":4,"name":"Marko","currency":"EUR"}
```

### 2. Излистај клиенти

```bash
curl -s http://localhost:8080/api/clients
```

```json
{"data":[{"id":1,"name":"Ana","currency":"EUR"},{"id":2,"name":"Bojan","currency":"EUR"},{"id":3,"name":"Cvetanka","currency":"EUR"}]}
```

### 3. Прикажи клиент (готовина + сопственост)

```bash
curl -s http://localhost:8080/api/clients/1
```

```json
{"data":{"id":1,"name":"Ana","currency":"EUR"},"cash":"860.00","holdings":{"AAPL":2}}
```

Полињата на клиентот се под `"data"`, додека `cash` и `holdings` се на исто ниво како негови браќа/сестри.

### 4. Состојба на готовина

```bash
curl -s http://localhost:8080/api/clients/1/balance
```

```json
{"cash":"860.00","currency":"EUR"}
```

### 5. Сопственост на инструменти

```bash
curl -s http://localhost:8080/api/clients/1/holdings
```

```json
{"holdings":{"AAPL":2}}
```

Целосно продадените инструменти се изоставени (не се враќаат со вредност нула). Цветанка (id 3) по seed:

```json
{"data":{"id":3,"name":"Cvetanka","currency":"EUR"},"cash":"1200.00","holdings":[]}
```

### 6. Излистај трансакции (со пагинација, најновите први)

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

### 7. Запиши трансакција

Единствена крајна точка (endpoint) за сите типови на движења. Испратете `type`: `deposit`, `withdrawal`, `buy`, или `sell`.

**Депозит** (движењата на готовина ги имаат `instrument`, `quantity` и `price` како `null`):

```bash
curl -s -X POST http://localhost:8080/api/clients/4/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","amount":"100.00"}'
```

```json
{"id":11,"client_id":4,"type":"deposit","amount":"100.00","instrument":null,"quantity":null,"price":null,"created_at":"2026-09-04T17:53:20+00:00"}
```

**Купување** (Buy):

```bash
curl -s -X POST http://localhost:8080/api/clients/4/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"buy","instrument":"AAPL","quantity":1,"price":"100.00"}'
```

Продавањето (`sell`) и повлекувањето (`withdrawal`) ја користат истата URL адреса со соодветните вредности за `"type"` и полињата.

### Примери за одбивање (422)

**Кршење на бизнис правило** — повлекување над состојбата (`LedgerService`):

```bash
curl -s -X POST http://localhost:8080/api/clients/1/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"withdrawal","amount":"9999.00"}'
```

```json
{"message":"Insufficient funds: balance is 860.00 EUR, requested 9999.00 EUR.","error":"insufficient_funds"}
```

**Неуспешна валидација** — негативен износ (`FormRequest`):

```bash
curl -s -X POST http://localhost:8080/api/clients/1/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","amount":"-10.00"}'
```

```json
{"message":"The amount field must be greater than 0.","errors":{"amount":["The amount field must be greater than 0."]}}
```

| Слој | Прашање | Форма на HTTP 422 |
|------|---------|-------------------|
| **FormRequest** | Дали барањето е правилно обликувано? | `{ "message": "...", "errors": { "field": ["..."] } }` |
| **LedgerService** | Дали движењето е дозволено со оглед на моменталната состојба/сопственост? | `{ "message": "...", "error": "insufficient_funds" }` или `"insufficient_holdings"` |

## Бизнис правила

Две инваријанти се применуваат при секое запишување:

1. **Без негативно салдо** — клиентот не може да повлече или купи со повеќе готовина отколку што поседува. Кршењето на правилото враќа HTTP 422 со `"error": "insufficient_funds"`. Дневникот останува непроменет (не се додава делумен ред).
2. **Без прекумерна продажба (short selling)** — клиентот не може да продаде повеќе единици од некој инструмент отколку што реално поседува. Кршењето враќа HTTP 422 со `"error": "insufficient_holdings"`. Дневникот останува непроменет.

Одбивањата се атомски: тестовите потврдуваат дека `Transaction::count()`, готовината и сопственоста остануваат исти по неуспешно движење.

## Стартување на тестови

```bash
./vendor/bin/sail artisan test
```

23 тестови ги покриваат бизнис правилата на дневникот (`LedgerServiceTest`), HTTP 201/422 одговорите, валидацијата на влезните податоци и целосното сценарио на Ана од почеток до крај. Тестовите ја користат PostgreSQL базата за тестирање (`DB_CONNECTION=pgsql`, `DB_DATABASE=testing` во `phpunit.xml`).

## Зошто вака?

**Дневникот како единствен извор на вистината (Single Source of Truth).** Готовината и сопственоста се SQL агрегации врз табелата `transactions`. Не постојат посебни колони за состојба или сопственост кои би можеле да отстапат или да се десинхронизираат од дневникот.

**Интегер центи внатрешно.** Износите се чуваат како `amount_cents` (и `price_cents` за тргување). `LedgerService` работи исклучиво со цели броеви (integers); `Money::fromDecimal()` / `Money::toDecimal()` вршат конверзија на самата API граница, така што децималните броеви (floats) никогаш не учествуваат во математичките пресметки. Свесно оставив претпоставка за 2 децимали бидејќи задачата работи со една валута по клиент без FX; за во продукција би додал мапа со експоненти за секоја валута.

**Два слоја на валидација.** Form Requests ги одбиваат лошо обликуваните податоци (погрешни полиња, нула количина, негативни износи) пред да се активира услугата (service). `LedgerService` ги одбива движењата кои се добро обликувани, но ги кршат сметководствените правила. Различните форми на 422 јасно покажуваат кој слој го фатил проблемот.

**Конкурентност (Concurrency).** Секое запишување се извршува внатре во `DB::transaction` со песимистичко заклучување (`lockForUpdate`) на редот на клиентот, така што две конкурентни барања не можат истовремено да ја поминат проверката на салдото и да ја одведат сметката во минус.

**Една заедничка крајна точка (endpoint) за трансакции.** Еден `POST /api/clients/{id}/transactions` прифаќа поле `type` наместо четири посебни рути. Ова го одржува API-то компактно; компромисот е што самите рути повеќе не ја опишуваат операцијата експлицитно — туку тоа го прави полето `type`.

**Инструментот како слободен текстуален етикета.** Тикерите се нормализираат во големи букви (uppercase) при запишување. Не постои каталог на инструменти; ознаката е она што операторот ќе го внесе.

**Без автентикација во оваа задача.** Фокусот на задачата е врз точноста на дневникот. Во продукција, би ги заштитил POST/запишувачките рути со автентикација на персоналот (на пр. преку Sanctum токени).

## Што би додал со повеќе време

- **Идемпотентни клучеви (Idempotency keys)** на `POST /transactions` за мрежните ретраи (retries) да не можат да дуплираат записи во дневникот.
- **Автентикација на персоналот** (Sanctum) за запишувачките рути — не улоги и дозволи (authorization), што е посебен сегмент.
- **Построги лимити (Rate limiting)** на запишувачките рути.
- **Cursor пагинација** за клиенти со долги истории на трансакции.
- **Форматирање прилагодено на валутата** — ISO експонент мапа (`Money::toDecimal($minor, $currency)`), преименување на `amount_cents` во `amount_minor`.
- **Ревизорски дневник (Audit log)** што го поврзува секое запишано движење со корисникот од персоналот кој го внел.

---

## English

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
