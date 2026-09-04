# Bruno API collection

Keep manual API collections in repo-root `bruno/` mirroring [`routes/api.php`](routes/api.php).

Use the **local** environment with `baseUrl` (default `http://localhost` for Sail) and `clientId` for nested client routes.

Always send `Accept: application/json` on API requests. Send `Content-Type: application/json` on POST bodies.

Group requests by domain (`clients/`, `transactions/`, `scenarios/`) and document expected HTTP status in each request's `docs` block.

Use a post-response script on create-client to set `clientId` from the response for chained manual testing.
