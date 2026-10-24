# Security Audit Remediation — 2026-10-23

Branch: `security/audit-fixes-2026-10`

This report records the remediation of every finding in the production-readiness
and security audit (S-01 … S-09), plus additional issues found while
implementing the fixes.

## Verification summary

| Check | Result |
|-------|--------|
| `php artisan test` | **348 passed**, 918 assertions (SQLite in-memory) |
| `composer audit` | No security advisories |
| `composer validate --strict` | Valid |
| `npm audit --audit-level=high` | 0 vulnerabilities |
| `php -l` on changed PHP files | No syntax errors |
| Docker image build | Covered by the new CI workflow (Docker is not installed on the local audit machine) |
| E2E suite | Updated for the admin-verification flow; run locally/CI with a browser |

## Audit findings

### S-01 — Laravel 11 is end-of-life

**Risk:** unsupported framework releases stop receiving security fixes.

**Remediation:**
- Upgraded `laravel/framework` from 11 to **12.70.1** (`composer.json` now
  requires `^12.0`).
- Updated framework integration points for Laravel 12 (bootstrap/middleware,
  session configuration, test assertions).
- Full test suite re-run after the upgrade.

**Evidence:** `composer.json`, `composer.lock`, `bootstrap/app.php`, Git history
for the upgrade commit.

### S-02 — PHP 8.2 is end-of-life

**Risk:** unsupported PHP receives no security patches.

**Remediation:**
- Production container upgraded from `php:8.2-cli` to **`php:8.4-cli`**.
- Added the required extensions (`bcmath`, `zip`, `opcache`) and a hardened
  PHP runtime configuration.
- Local development remains supported on PHP 8.2+; CI tests PHP 8.2, 8.3, and
  8.4 so upgrades do not silently break older local machines.

**Evidence:** `Dockerfile`, `.github/workflows/ci.yml`.

### S-03 — No rate limiting on authentication or abuse-prone endpoints

**Risk:** credential stuffing, password-reset email flooding, registration
spam, payment-spam, suggestion scraping, and community spam.

**Remediation (named limiters in `AppServiceProvider` + route middleware):**

| Endpoint | Limit |
|----------|-------|
| `POST /login` | 20/min per IP **and** 5 failed attempts/min per email+IP; cleared on success |
| `POST /register` | 5/min per IP |
| `POST /forgot-password` | 3/min per email+IP and 10/min per IP |
| `POST /reset-password` | 6/min per IP |
| `GET /search/suggestions` | 60/min per IP |
| `POST /community/submit` | 5/min per user |
| `POST /payment/{order}` | 6/min per user |

Rate-limit responses are generic and do not disclose whether an account exists.
Eight regression tests cover the limits, including limiter clearing after a
successful login.

**Evidence:** `app/Providers/AppServiceProvider.php`,
`app/Http/Controllers/Auth/LoginController.php`, `routes/web.php`,
`tests/Feature/RateLimitTest.php`.

### S-04 — No queue worker

**Risk:** queued jobs (notifications, future payment reconciliation) never run;
`QUEUE_CONNECTION=database` silently accumulates rows.

**Remediation:**
- The container now runs **two Supervisor programs**: the web server and
  `php artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600`.
- Both run as the unprivileged `www-data` user.
- Startup builds config/route/view caches and runs migrations as `www-data`.

**Evidence:** `docker/supervisord.conf`, `docker/start.sh`, `Dockerfile`.

### S-05 — Payment was marked paid from a self-reported TrxID

**Risk:** a customer could type any unique string and the system immediately
marked the order paid, decremented stock, and counted revenue — with no proof
that money moved.

**Remediation:**
- Submitting a TrxID now **reserves stock** and leaves the order `unpaid` with
  `payment_submitted_at` set. It is shown as **“Awaiting verification”**.
- Admins see **Verify payment** / **Reject payment** actions on the order page.
  - Verify → `paid`, stamps `payment_verified_at` and `payment_verified_by`.
  - Reject → `failed`, releases the reserved stock and notifies wishlists.
- Customers cannot resubmit while a payment is pending; after rejection they
  may submit a new TrxID, which reserves stock again.
- Cancelling a pending or paid order releases reserved stock exactly once.
- TrxIDs are uppercased, restricted to letters/numbers/hyphens, validated for
  uniqueness, and protected by a **database unique index** to close the
  concurrent-submission race.
- Revenue and “units sold” analytics continue to count only `paid` orders.

**Evidence:** `app/Http/Controllers/PaymentController.php`,
`app/Http/Controllers/Admin/OrderController.php`, `app/Models/Order.php`,
`database/migrations/2026_10_23_000001_add_payment_verification_to_orders_table.php`,
order/admin Blade views, `tests/Feature/PaymentVerificationTest.php`,
`tests/Feature/ShopFlowTest.php`.

### S-06 — Password changes did not invalidate other sessions

**Risk:** a stolen session or “remember me” cookie remained valid after the
user changed their password.

**Remediation:**
- Password changes now:
  - rotate `remember_token`,
  - call `Auth::logoutOtherDevices($newPassword)`,
  - regenerate the session ID.
- `AuthenticateSession` middleware is registered globally on the web group, so
  sessions holding the old password digest are signed out on their next request.
- Forced temporary-password changes still work without knowing the old password.

**Evidence:** `app/Http/Controllers/Auth/PasswordController.php`,
`bootstrap/app.php`, `tests/Feature/PasswordSecurityTest.php`.

### S-07 — Missing CSP and HSTS

**Remediation:** `SecurityHeaders` middleware now emits:
- `Content-Security-Policy` with `default-src 'self'`, `object-src 'none'`,
  `base-uri 'self'`, `frame-ancestors 'self'`, `form-action 'self'`, and
  explicit allow-lists for jsDelivr and Google Fonts.
- `Strict-Transport-Security` (`max-age=31536000; includeSubDomains`) only on
  HTTPS requests in production.
- `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, `Cross-Origin-Opener-Policy`,
  `Cross-Origin-Resource-Policy`, `X-Permitted-Cross-Domain-Policies`, and
  `X-XSS-Protection: 0`.
- `upgrade-insecure-requests` on HTTPS pages.

The CSP keeps `'unsafe-inline'` because Blade still contains inline scripts and
style attributes. Removing it requires nonce/hash migration and is documented
as a follow-up rather than silently breaking the storefront.

**Evidence:** `app/Http/Middleware/SecurityHeaders.php`,
`tests/Feature/HttpSecurityTest.php`.

### S-08 — Unsafe `.env` defaults

**Risk:** debug mode on, unencrypted sessions, insecure cookies, and unclear
production requirements.

**Remediation:**
- `.env.example`: `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, documented
  `SESSION_SECURE_COOKIE`, `TRUSTED_PROXIES`, and `ALLOW_DEMO_SEED=false`.
- `config/session.php`: session encryption defaults to on; secure cookies
  default to true when `APP_ENV=production`.
- Added `.env.production.example` with production-safe logging, session,
  proxy, mail, and database placeholders.
- Trusted-proxy handling added in `bootstrap/app.php` so HTTPS detection and
  client IPs work behind Railway/a load balancer.
- `APP_KEY` is never generated or committed automatically in production.

**Evidence:** `.env.example`, `.env.production.example`, `config/session.php`,
`bootstrap/app.php`, `Dockerfile`, `docker/start.sh`.

### S-09 — Dependency and CI hygiene

**Findings:** the abandoned `heroku/heroku-buildpack-php` package was listed in
`require`, Composer's audit block was disabled, and there was no CI pipeline.

**Remediation:**
- Removed the unused Heroku buildpack package from `require`.
- Set Composer `config.audit.block-insecure=true`.
- Added `.github/workflows/ci.yml`:
  - PHP 8.2 / 8.3 / 8.4 test matrix,
  - `composer validate --strict`,
  - `composer audit`,
  - `npm ci` + `npm audit --audit-level=high`,
  - Docker image build.
- Added weekly Dependabot updates for Composer, npm, GitHub Actions, and Docker.
- Added `SECURITY.md` with private reporting and a deployment checklist.

**Evidence:** `composer.json`, `.github/workflows/ci.yml`,
`.github/dependabot.yml`, `SECURITY.md`.

## Additional issues found and fixed

| Issue | Fix |
|-------|-----|
| Account enumeration via forgot-password errors | Always return a generic success message; tests updated |
| Email case created duplicate/ambiguous accounts | Normalise registration and login emails to lowercase |
| TrxID race could bypass `Rule::unique` | Database unique index + `QueryException` handling |
| Order pagination was nondeterministic when timestamps tied | Added `id` as a secondary sort key |
| Demo seeder created known-password accounts in production | Refuses to run when `APP_ENV=production` unless `ALLOW_DEMO_SEED=true` |
| Hot dashboard/catalogue queries lacked composite indexes | Added indexes for orders, products, community, users, care guides, wishlists |
| Rejected/cancelled payments could strand reserved stock | Rejection and cancellation release stock and restore notifications |
| Cancelled orders could still be paid and reserve new stock | Payment show/process (and the locked transaction re-check) reject cancelled orders |
| Pending payments were hard to find for admins | Added a **Payment pending** order filter with a count and a dashboard “Needs attention” alert |
| E2E cleanup only restored stock for `paid` orders | Updated for the new reserved-stock state |
| Container had no healthcheck or queue worker | Added `/up` healthcheck and Supervisor queue program |
| Public seed media lost behind a Railway volume | Preserved in `/app/seed-storage` and restored on boot |

## Deliberately deferred / accepted risks

1. **Live payment gateway verification.** bKash/Nagad do not offer a public
   merchant API for this project scope, so an admin still verifies transfers
   manually. The important security boundary — no automatic `paid` state from
   user input — is fixed.
2. **CSP `'unsafe-inline'`.** The current Blade views use inline scripts.
   A nonce/hash-based CSP is the next step when the frontend is refactored.
3. **`php artisan serve` in the container.** This is suitable for the
   project's scale/demo deployment. A high-traffic production deployment should
   move to php-fpm + nginx (or FrankenPHP) and a managed database.
4. **Manual courier tracking.** Courier integration remains simulated by
   design.
5. **No real SMTP by default.** Production must supply a real mail transport;
   the template documents this.

## How to reproduce the verification

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan test
composer audit
composer validate --strict
npm ci
npm audit --audit-level=high
docker build -t sordar-agro .
```

## Deployment notes for existing databases

The payment-verification migration
(`2026_10_23_000001_add_payment_verification_to_orders_table.php`) adds a unique
index on `orders.transaction_id`. Before running it against an existing
database, check for duplicates:

```sql
SELECT transaction_id, COUNT(*) AS uses
FROM orders
WHERE transaction_id IS NOT NULL
GROUP BY transaction_id
HAVING COUNT(*) > 1;
```

Resolve any duplicate rows manually before deploying — each TrxID belongs to a
single transfer. Multiple `NULL` values are allowed. Existing `paid` orders keep
their status; existing `unpaid` orders with a TrxID become “Awaiting
verification” and can be verified or rejected by an admin. See `SECURITY.md`
for the full deployment checklist.
