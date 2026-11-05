# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| `main` (Laravel 12.x) | Yes |
| Anything older | No |

Security fixes are applied to `main` and released from there.

## Reporting a vulnerability

Please **do not open a public issue** for a security problem.

Use GitHub's private vulnerability reporting:

1. Open the repository's **Security** tab.
2. Choose **Report a vulnerability**.
3. Include the affected route/file, reproduction steps, impact, and any
   suggested fix.

You should receive an acknowledgement within **5 business days**. Please give
us reasonable time to investigate and ship a fix before public disclosure.

When testing, use your own account and data. Do not access other users'
data, run denial-of-service tests, or test against a live production instance
without written permission.

## Security controls in this application

- **Authentication and roles** — hand-rolled session authentication with
  `customer` / `seller` / `admin` middleware, active-account checks, and
  server-side ownership checks on orders and payments.
- **Passwords** — hashed with Laravel's `hashed` cast; password changes rotate
  `remember_token`, invalidate other sessions via `AuthenticateSession` /
  `logoutOtherDevices`, and regenerate the session ID.
- **Rate limiting** — login (per IP and per email+IP), registration, password
  reset requests, search suggestions, community submissions, and payment
  submissions are throttled.
- **Payment verification** — a customer-submitted bKash/Nagad TrxID is treated
  as a claim, not proof of payment. Stock is reserved on submission and an
  admin must verify the transaction before the order becomes `paid`. Rejected
  or cancelled payments release the reserved stock.
- **Transaction IDs** — normalised, validated, checked for uniqueness, and
  protected by a database unique index (including the concurrent-submission
  race).
- **HTTP headers** — CSP, HSTS (HTTPS + production only), `X-Content-Type-Options`,
  `X-Frame-Options`, Referrer-Policy, Permissions-Policy, COOP, CORP, and
  `X-Permitted-Cross-Domain-Policies`. The CSP is a baseline and still allows
  inline scripts because the Blade views use inline `<script>` blocks.
- **Sessions** — encrypted by default, `HttpOnly`, `SameSite=Lax`, and
  `Secure` automatically in production. Trusted proxies are configurable so
  client IPs and HTTPS detection work behind a TLS-terminating proxy.
- **Secrets** — `.env` is ignored by Git. Production configuration must come
  from the hosting provider's secret manager; only template files are
  committed.
- **Demo data** — the demo seeder refuses to run when `APP_ENV=production`
  unless `ALLOW_DEMO_SEED=true` is explicitly set (for throwaway demo
  deployments only).
- **Dependencies** — `composer audit` runs in CI and Composer is configured
  with `audit.block-insecure=true`. Dependabot checks Composer, npm,
  GitHub Actions, and Docker weekly.

## Verifying a payment

When an admin sees an order in the **Payment pending** queue, the following
manual verification steps must be completed before pressing **Verify**:

1. Open the order in the admin dashboard and note the **TrxID**, **amount**,
   and **payment method** (bKash or Nagad).
2. Log in to the corresponding mobile-money merchant/personal statement.
3. Find the transaction matching the submitted TrxID.
4. Confirm all three match:
   - **TrxID** — exact match with the customer's submission.
   - **Amount** — matches the order total (to the taka).
   - **Sender number** — matches the customer's registered phone number.
5. If all three match, press **Verify payment**. The order becomes `paid`.
6. If any detail does not match, press **Reject payment**. This marks the
   payment as `failed` and **releases the reserved stock** back to inventory.

> **Note:** Rejecting a payment does not prevent the customer from submitting
> a new TrxID later. The stock reservation cycle restarts on each new
> submission.

## Queue health monitoring

The `queue:check-health` command runs hourly via the Laravel scheduler. It
checks the `failed_jobs` table and logs an error if any jobs have failed. In
production, ensure the scheduler is running (`php artisan schedule:work` or a
cron entry) so this check executes automatically.

## Verifying a payment

When an admin sees an order in the **Payment pending** queue, the following
manual verification steps must be completed before pressing **Verify**:

1. **Open the order** in the admin dashboard and note the submitted TrxID,
   the order total, and the payment method (bKash or Nagad).
2. **Log in** to the corresponding mobile-money account (bKash or Nagad)
   and open the transaction statement for the relevant date.
3. **Match the TrxID** — find the transaction whose TrxID exactly matches
   the one submitted by the customer.
4. **Match the amount** — confirm the transaction amount equals the order
   total. Partial payments are not valid.
5. **Match the sender number** — confirm the sender's mobile number matches
   the customer's registered phone number or is otherwise explainable
   (e.g. a family member paying on their behalf — use judgement).
6. **Verify** — if all three match (TrxID + amount + sender), press
   **Verify payment**. The order becomes `paid` and can be fulfilled.
7. **Reject** — if any detail does not match, press **Reject payment**.
   This marks the payment as `failed` and **releases the reserved stock**
   back to inventory. The customer may then submit a new TrxID.

> **Important:** A TrxID is a *claim*, not proof of payment. Never verify
> based on the TrxID alone — always cross-reference the amount and sender
> against the actual mobile-money statement.

## Queue health monitoring

The `queue:check-health` command runs hourly via the Laravel scheduler. It
checks the `failed_jobs` table and logs an error when failed jobs are
detected, indicating a stuck queue worker or downstream service failure.
In production, ensure the scheduler is running (via cron:
`* * * * * php /path-to-app/artisan schedule:run >> /dev/null 2>&1`).

## Deployment checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
- [ ] A persistent `APP_KEY` supplied through the environment
- [ ] Database credentials supplied through the environment
- [ ] `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`
- [ ] `TRUSTED_PROXIES` set to the actual proxy/load balancer
- [ ] `ALLOW_DEMO_SEED` unset or `false`
- [ ] Demo accounts removed or passwords changed if a database was copied
      from a demo environment
- [ ] A real mail transport configured (not `log`)
- [ ] `QUEUE_CONNECTION=database` and a running queue worker
      (the Docker image runs one under Supervisor)
- [ ] HTTPS terminated at the proxy, with HSTS observed on the first response
- [ ] Duplicate non-null `orders.transaction_id` rows resolved **before** running
      the payment-verification migration (see below)
- [ ] `php artisan migrate --force` run during deploy
- [ ] Backups and log retention configured by the hosting provider

## Backups

Database and uploaded-media backups are the hosting provider's responsibility.
The application stores two things that must be backed up continuously:

1. **Database** — all application tables, with the `orders`, `payments`,
   `products`, `users`, and `audit`-relevant tables taking priority. Back up
   **before** running the payment-verification migration so a duplicate
   `transaction_id` resolution can be triaged against the pre-migration state.
   On MySQL, use `mysqldump --single-transaction --quick` (or the provider's
   managed backup) and encrypt at rest.

2. **Media** — product images and seeded media live in `storage/app/public`
   (mounted as a persistent volume in the Docker image). These are not in Git
   and must be backed up by the hosting provider's volume-snapshot mechanism.

### Backup retention

- **Hourly** point-in-time recovery (binary logs / WAL) for the last 24 h.
- **Daily** full backups retained for **14 days**.
- **Weekly** full backups retained for **12 weeks**.

### Restore verification

The hosting provider must run a restore-verification drill at least monthly:
restore the latest daily backup to an isolated database, run
`php artisan migrate:status` to confirm the schema is clean, and boot the
application read-only against it. Store the verification result as evidence.

### Secrets in backups

Never log or back up the `.env` file or secret-manager values. Restore
secrets only from the provider's secret manager into the new environment; do
not rely on a `.env` file left on disk.

## Upgrading an existing installation

The payment-verification migration adds a **unique index** on
`orders.transaction_id`. Existing rows are otherwise left unchanged: orders
already marked `paid` stay paid, and orders that are `unpaid` with a TrxID are
treated as pending verification.

Before deploying to a database that already has orders, check for duplicates:

```sql
SELECT transaction_id, COUNT(*) AS uses
FROM orders
WHERE transaction_id IS NOT NULL
GROUP BY transaction_id
HAVING COUNT(*) > 1;
```

If any rows are returned, investigate and resolve them manually (the TrxID
belongs to one real transfer) **before** running the migration. The unique
index intentionally allows multiple `NULL` values, so unpaid orders without a
TrxID are unaffected. On MySQL, also make sure the application user has
`ALTER`/index-creation privileges for the migration.
