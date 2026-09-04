# Prime Capital Portfolio API

Choose language / Изберете јазик:
- [English](#english)
- [Македонски (Macedonian)](#македонски-macedonian)

---

## English

Append-only ledger API for client cash and instrument holdings. Every deposit, withdrawal, buy, and sell is stored as an immutable transaction row. Cash balance and holdings are derived from that ledger in SQL — they are never stored separately. Business rules are enforced on every write: a client cannot spend more cash than they have, and cannot sell more of an instrument than they hold.

## Requirements

- **Docker Desktop** or **Docker Compose** (Laravel Sail runs the app and PostgreSQL)
- **WSL2 (Ubuntu)** on Windows (highly recommended for optimal Docker container performance)
- **PHP 8.5+** and **Composer** (needed once on the host for the initial `composer install` before Sail takes over)

## Local setup

```bash
git clone https://github.com/Nikolaa00/PRIME-CAPITAL-PORTFOLIO-API.git
cd PRIME-CAPITAL-PORTFOLIO-API
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
```

```json
{"data":{"id":1,"name":"Ana","currency":"EUR"},"cash":"860.00","holdings":{"AAPL":2}}
```

## Talking to the API

Send `Accept: application/json` on every request. POST bodies use `Content-Type: application/json`.

| Method | Path | What it does |
|--------|------|----------------|
| `POST` | `/api/clients` | Create a client |
| `GET` | `/api/clients` | List clients |
| `GET` | `/api/clients/{id}` | Show client + cash + holdings |
| `GET` | `/api/clients/{id}/balance` | Cash only |
| `GET` | `/api/clients/{id}/holdings` | Holdings only (zeros omitted) |
| `GET` | `/api/clients/{id}/transactions` | Paginated ledger, newest first |
| `POST` | `/api/clients/{id}/transactions` | Record deposit, withdrawal, buy, or sell |

**GET** — seeded Ana after `migrate --seed`:

```bash
curl -s http://localhost:8080/api/clients/1
```

```json
{"data":{"id":1,"name":"Ana","currency":"EUR"},"cash":"860.00","holdings":{"AAPL":2}}
```

**POST** — deposit on a new client (create Marko first, then use his `id`):

```bash
curl -s -X POST http://localhost:8080/api/clients \
  -H "Content-Type: application/json" \
  -d '{"name":"Marko","currency":"EUR"}'
```

```json
{"id":4,"name":"Marko","currency":"EUR"}
```

```bash
curl -s -X POST http://localhost:8080/api/clients/4/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","amount":"100.00"}'
```

```json
{"id":11,"client_id":4,"type":"deposit","amount":"100.00","instrument":null,"quantity":null,"price":null,"created_at":"2026-09-04T17:53:20+00:00"}
```

`type` is `deposit`, `withdrawal`, `buy`, or `sell`. Buys/sells need `instrument`, `quantity`, and `price`. Full request set is in the Bruno collection below.

## Manual Testing with Bruno

The project includes a complete [Bruno](https://www.usebruno.com/) collection for API testing. You can use the Bruno Desktop App, the Bruno VS Code extension, or the Bruno web client.

1. Install Bruno or the VS Code extension.
2. Open Bruno and select **Open Collection**.
3. Choose the `bruno/` folder in the root of this repository.
4. Select the **local** environment from the environment dropdown in the top-right corner (configured for `http://localhost:8080`).
5. Run requests from the following folders:
   - `clients/` — Create, list, show, and check balance/holdings of clients.
   - `transactions/` — Record deposits, withdrawals, buys, sells, and list paginated transactions.
   - `scenarios/` — Step-by-step requests replicating the mentor's evaluation scenario (Ana's scenario).

## Running Seeders & Tests

- **Run migrations and seeders:**
  ```bash
  ./vendor/bin/sail artisan migrate --seed
  ```
- **Run seeders only:**
  ```bash
  ./vendor/bin/sail artisan db:seed
  ```
- **Run tests (PHPUnit):**
  ```bash
  ./vendor/bin/sail artisan test
  # Or run phpunit directly:
  ./vendor/bin/sail bin phpunit
  ```

There are 21 tests covering ledger domain rules (`LedgerServiceTest`), HTTP 201/422 responses, input validation, and the full Ana scenario end-to-end. Tests use the PostgreSQL `testing` database (`DB_CONNECTION=pgsql`, `DB_DATABASE=testing` in `phpunit.xml`).

## Business rules

Two invariants apply on every write:

1. **No negative balance** — a client cannot withdraw or buy with more cash than they hold. Violations return HTTP 422 with `"error": "insufficient_funds"`. The ledger is unchanged (no partial row).
2. **No overselling** — a client cannot sell more units of an instrument than they hold. Violations return HTTP 422 with `"error": "insufficient_holdings"`. The ledger is unchanged.

Rejections are atomic: tests assert `Transaction::count()`, cash, and holdings stay the same after a failed move.

## Why this way

**Ledger as source of truth.** Cash and holdings are SQL aggregates over `transactions`. There are no balance or holdings columns to drift out of sync with the journal.

**Integer cents internally.** Amounts are stored as `amount_cents` (and `price_cents` for trades). `LedgerService` works only in integers; `Money::fromDecimal()` / `Money::toDecimal()` convert at the API boundary so floats never touch money math. I consciously left a 2-decimal assumption because the task involves one currency per client without FX; for production I would add a currency exponent map.

**Two validation layers.** Form requests reject malformed input (wrong fields, zero quantity, negative amounts) before the service runs. `LedgerService` rejects moves that are well-formed but break account rules. Different 422 shapes make it clear which layer caught the problem:
- **FormRequest (validation failure):** `{ "message": "...", "errors": { "field": ["..."] } }`
- **LedgerService (business rule violation):** `{ "message": "...", "error": "insufficient_funds" }` or `"insufficient_holdings"`

**Concurrency.** Each write runs inside `DB::transaction` with `lockForUpdate` on the client row, so two concurrent requests cannot both pass a balance check and overdraw the account.

**One transactions endpoint.** A single `POST /api/clients/{id}/transactions` accepts a `type` field instead of four separate routes. This keeps the API small; the trade-off is that route naming no longer spells out the operation — the `type` field does.

**Instrument as free-text label.** Tickers are normalized to uppercase on write. There is no instrument catalogue; the label is whatever the operator enters.

**No authentication in this task.** The assignment focuses on ledger correctness. In production I would protect write endpoints with staff authentication (e.g. Sanctum tokens).

## What I'd add with more time

- **Idempotency keys** on `POST /transactions` — client sends `Idempotency-Key`; server returns the same result for retries instead of duplicating ledger entries (different from Request ID, which is for tracing, not deduplication).
- **Request ID** on every response (`X-Request-Id`) and in logs — so support can investigate a reported error by searching one ID instead of guessing from timestamps.
- **Staff authentication** (Sanctum) on write endpoints — who is allowed to post movements; separate from role-based authorization.
- **Audit log** — tie each posted transaction to the staff user who recorded it (who, when, which client).
- **Cursor pagination** for `GET /transactions` — append-only ledgers grow without bound; cursors stay stable under concurrent writes.

---

## Македонски (Macedonian)

API базирано на додатен дневник (append-only ledger) за готовина и сопственост на инструменти на клиенти. Секој депозит, повлекување, купување и продавање се зачувува како неменлив (immutable) ред во трансакциите. Состојбата на готовина и портфолиото се изведуваат директно од тој дневник преку SQL агрегации — тие никогаш не се зачувуваат како посебни колони во базата. Бизнис правилата се применуваат при секое запишување: клиентот не може да потроши повеќе готовина отколку што има, и не може да продаде повеќе парчиња од некој инструмент отколку што поседува.

## Барања

- **Docker Desktop** или **Docker Compose** (Laravel Sail ги стартува апликацијата и PostgreSQL)
- **WSL2 (Ubuntu)** на Windows (исклучително препорачано за оптимални перформанси на Docker контејнерите)
- **PHP 8.5+** и **Composer** (потребни се само еднаш на хостот за почетниот `composer install` пред да преземе Sail)

## Локално подигнување

```bash
git clone https://github.com/Nikolaa00/PRIME-CAPITAL-PORTFOLIO-API.git
cd PRIME-CAPITAL-PORTFOLIO-API
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
```

```json
{"data":{"id":1,"name":"Ana","currency":"EUR"},"cash":"860.00","holdings":{"AAPL":2}}
```

## Комуникација со API-то

На секое барање пратете `Accept: application/json`. Телото на POST барањата користи `Content-Type: application/json`.

| Метод | Патека | Што прави |
|--------|------|----------------|
| `POST` | `/api/clients` | Креира клиент |
| `GET` | `/api/clients` | Листа клиенти |
| `GET` | `/api/clients/{id}` | Прикажува клиент + готовина + сопственост |
| `GET` | `/api/clients/{id}/balance` | Само готовина |
| `GET` | `/api/clients/{id}/holdings` | Само сопственост (нулите се изоставени) |
| `GET` | `/api/clients/{id}/transactions` | Пагиниран дневник, најновите први |
| `POST` | `/api/clients/{id}/transactions` | Запишува депозит, повлекување, купување или продавање |

**GET** — Ана по `migrate --seed`:

```bash
curl -s http://localhost:8080/api/clients/1
```

```json
{"data":{"id":1,"name":"Ana","currency":"EUR"},"cash":"860.00","holdings":{"AAPL":2}}
```

**POST** — депозит на нов клиент (прво креирај го Марко, па користи го неговиот `id`):

```bash
curl -s -X POST http://localhost:8080/api/clients \
  -H "Content-Type: application/json" \
  -d '{"name":"Marko","currency":"EUR"}'
```

```json
{"id":4,"name":"Marko","currency":"EUR"}
```

```bash
curl -s -X POST http://localhost:8080/api/clients/4/transactions \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","amount":"100.00"}'
```

```json
{"id":11,"client_id":4,"type":"deposit","amount":"100.00","instrument":null,"quantity":null,"price":null,"created_at":"2026-09-04T17:53:20+00:00"}
```

`type` е `deposit`, `withdrawal`, `buy` или `sell`. Купувањето и продавањето бараат `instrument`, `quantity` и `price`. Целосната колекција е во Bruno подолу.

## Мануелно тестирање со Bruno

Проектот вклучува комплетна [Bruno](https://www.usebruno.com/) колекција за тестирање на API-то. Можете да ја користите апликацијата Bruno Desktop, екстензијата за VS Code или веб-клиентот на Bruno.

1. Инсталирајте го Bruno или соодветната VS Code екстензија.
2. Отворете го Bruno и изберете **Open Collection**.
3. Изберете го фолдерот `bruno/` во коренот на овој репозиториум.
4. Изберете ја околината **local** од паѓачкото мени во горниот десен агол (конфигурирана за `http://localhost:8080`).
5. Извршете ги барањата од следниве фолдери:
   - `clients/` — Креирање, листање, прикажување и проверка на состојба/сопственост на клиентите.
   - `transactions/` — Запишување депозити, повлекувања, купувања, продавања и листање на пагинирани трансакции.
   - `scenarios/` — Чекор-по-чекор барања кои го реплицираат сценариото за евалуација на менторот (сценариото за Ана).

## Стартување на сидери и тестови

- **Стартување на миграции и сидери:**
  ```bash
  ./vendor/bin/sail artisan migrate --seed
  ```
- **Стартување само на сидери:**
  ```bash
  ./vendor/bin/sail artisan db:seed
  ```
- **Стартување на тестови (PHPUnit):**
  ```bash
  ./vendor/bin/sail artisan test
  # Или директно со phpunit:
  ./vendor/bin/sail bin phpunit
  ```

Вкупно 21 тестови ги покриваат бизнис правилата на дневникот (`LedgerServiceTest`), HTTP 201/422 одговорите, валидацијата на влезните податоци и целосното сценарио на Ана од почеток до крај. Тестовите ја користат PostgreSQL базата за тестирање (`DB_CONNECTION=pgsql`, `DB_DATABASE=testing` во `phpunit.xml`).

## Бизнис правила

Две инваријанти се применуваат при секое запишување:

1. **Без негативно салдо** — клиентот не може да повлече или купи со повеќе готовина отколку што поседува. Кршењето на правилото враќа HTTP 422 со `"error": "insufficient_funds"`. Дневникот останува непроменет (не се додава делумен ред).
2. **Без прекумерна продажба (short selling)** — клиентот не може да продаде повеќе единици од некој инструмент отколку што реално поседува. Кршењето враќа HTTP 422 со `"error": "insufficient_holdings"`. Дневникот останува непроменет.

Одбивањата се атомски: тестовите потврдуваат дека `Transaction::count()`, готовината и сопственоста остануваат исти по неуспешно движење.

## Зошто вака?

**Дневникот како единствен извор на вистината (Single Source of Truth).** Готовината и сопственоста се SQL агрегации врз табелата `transactions`. Не постојат посебни колони за состојба или сопственост кои би можеле да отстапат или да се десинхронизираат од дневникот.

**Интегер центи внатрешно.** Износите се чуваат како `amount_cents` (и `price_cents` за тргување). `LedgerService` работи исклучиво со цели броеви (integers); `Money::fromDecimal()` / `Money::toDecimal()` вршат конверзија на самата API граница, така што децималните броеви (floats) никогаш не учествуваат во математичките пресметки. Свесно оставив претпоставка за 2 децимали бидејќи задачата работи со една валута по клиент без FX; за во продукција би додал мапа со експоненти за секоја валута.

**Два слоја на валидација.** Form Requests ги одбиваат лошо обликуваните податоци (погрешни полиња, нула количина, негативни износи) пред да се активира услугата (service). `LedgerService` ги одбива движењата кои се добро обликувани, но ги кршат сметководствените правила. Различните форми на 422 јасно покажуваат кој слој го фатил проблемот:
- **FormRequest (неуспешна валидација):** `{ "message": "...", "errors": { "field": ["..."] } }`
- **LedgerService (кршење на бизнис правило):** `{ "message": "...", "error": "insufficient_funds" }` или `"insufficient_holdings"`

**Конкурентност (Concurrency).** Секое запишување се извршува внатре во `DB::transaction` со песимистичко заклучување (`lockForUpdate`) на редот на клиентот, така што две конкурентни барања не можат истовремено да ја поминат проверката на салдото и да ја одведат сметката во минус.

**Една заедничка крајна точка (endpoint) за трансакции.** Еден `POST /api/clients/{id}/transactions` прифаќа поле `type` наместо четири посебни рути. Ова го одржува API-то компактно; компромисот е што самите рути повеќе не ја опишуваат операцијата експлицитно — туку тоа го прави полето `type`.

**Инструментот како слободен текстуален етикета.** Тикерите се нормализираат во големи букви (uppercase) при запишување. Не постои каталог на инструменти; ознаката е она што операторот ќе го внесе.

**Без автентикација во оваа задача.** Фокусот на задачата е врз точноста на дневникот. Во продукција, би ги заштитил POST/запишувачките рути со автентикација на персоналот (на пр. преку Sanctum токени).

## Што би додал со повеќе време

- **Идемпотентни клучеви (Idempotency keys)** на `POST /transactions` — клиентот праќа `Idempotency-Key`; серверот го враќа истиот резултат при retry наместо да дуплира запис во дневникот (различно од Request ID, кој е за следење, не за дедупликација).
- **Request ID** на секој одговор (`X-Request-Id`) и во логовите — за поддршка да може да ја истражи пријавена грешка пребарувајќи по еден ID наместо по време.
- **Автентикација на персоналот** (Sanctum) за запишувачките рути — кој смее да постира движења; одделно од улоги и дозволи (authorization).
- **Ревизорски дневник (Audit log)** — секоја трансакција поврзана со корисникот од персоналот кој ја внел (кој, кога, за кој клиент).
- **Cursor пагинација** за `GET /transactions` — append-only дневникот расте без граница; cursor pagination останува стабилен при конкурентни записи.
