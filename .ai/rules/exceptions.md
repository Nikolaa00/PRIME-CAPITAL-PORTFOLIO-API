# Domain exceptions

Place business-rule exceptions in `app/Exceptions/` extending abstract `DomainRuleException`.

Implement `errorCode(): string` on each concrete exception. Use `ShouldntReport` via the base class so expected rule violations are not logged as errors.

Throw domain exceptions only from `LedgerService` (insufficient funds on withdraw/buy, insufficient holdings on sell). Do not throw them from controllers, form requests, or orchestration services.

Register JSON rendering for `DomainRuleException` in `bootstrap/app.php` as HTTP 422 with `{message, error}`.
