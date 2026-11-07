# Durable post-payment recovery

Payment confirmation commits the paid order, enrollments, and outbox rows in one database transaction. PostPaymentProcessor attempts delivery immediately; the `orders:recover-post-payment` command retries open rows independently of gateway callbacks. The Laravel scheduler invokes it every minute with `withoutOverlapping()`.

## Deployment

1. Deploy code and run the new database migrations using the normal deployment procedure.
2. Keep the Laravel scheduler running (`php artisan schedule:run` each minute) and the configured queue worker running.
3. Manual recovery is available through `php artisan orders:recover-post-payment`.

Do not run migrations or recovery against an unintended database. The implementation test suite uses SQLite in memory; it does not prove a production scheduler or queue worker is running.

## Delivery guarantee

License jobs are submitted before the outbox row is acknowledged, with `beforeCommit()` explicitly overriding queue connection defaults. A queue failure rolls back the acknowledgement, leaving the task retryable. A crash after queue acceptance but before database commit can produce duplicate delivery. Delivery is therefore **at least once**, not exactly once. The existing license job uses a per-user/course overlap lock and the license service reuses issued licenses.

Each task is isolated in its own transaction. Failure of a coupon task does not stop license delivery or cart cleanup. Rows are locked while processing so concurrent recovery commands do not normally dispatch the same open row simultaneously.

## Operational states

- `completed_at != null`: local work completed, or a license job was accepted by the queue. This does not mean the external license was issued; inspect license and failed-job state separately.
- `completed_at == null && rejected_at == null`: open/retryable. Inspect `attempts`, `last_attempt_at`, and `last_error` for persistent failures.
- `rejected_at != null`: permanent coupon business rejection requiring review; never represented as successfully recorded coupon usage. After resolving the business condition, an operator may explicitly clear `rejected_at` to rearm the task.

A failed external license job follows its own retry and failed-job handling. Unknown task types and invalid payloads remain open with an error for operator investigation. Legacy paid orders without outbox rows are not automatically backfilled, since that could unexpectedly clear current carts or issue licenses for historical purchases.

## Tests

`php vendor/phpunit/phpunit/phpunit --do-not-cache-result --filter "PostPayment|PurchaseAudit|ShopCheckout|SpotplayerLicense"`

Regression coverage includes command-driven recovery without a callback, queue failure and retry, terminal coupon rejection, transient coupon failure, independent task progress, and ignoring unpaid orders.
