# Sordar Agro Production Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close every finding in the supplied audit (S-01…S-09), remediate all `composer audit` advisories, and harden the deployment/authentication/payment paths without changing the storefront's user-facing business rules.

**Architecture:** Keep the existing Laravel monolith and Blade storefront. Upgrade the framework/runtime in place, add thin middleware/config layers for transport and abuse protection, and turn the self-reported payment flow into a reserve-then-admin-verify workflow. All behavior changes are covered by feature tests and documented in the repository.

**Tech Stack:** Laravel 12, PHP 8.4 container (PHP 8.2 minimum for local tooling), PHPUnit 11, GitHub Actions, Docker + supervisord, MySQL/SQLite.

**Spec:** `docs/implementation-brief.md` (§26 Security, §27 Deployment, §35 Phase 0) plus the audit findings supplied in the task (S-01…S-09).

## Global Constraints

- Do not rewrite the storefront or move off Blade.
- Preserve role separation (`customer`, `seller`, `admin`) and existing ownership checks.
- Preserve transactional stock control (`lockForUpdate`) — never replace it with client-side checks.
- Do not commit `.env`, credentials, or generated runtime files.
- Every behavior change gets a PHPUnit test; the full suite must be green before commit.
- `composer audit` must report zero advisories before completion.
- Commit on a dedicated branch; never push directly to `main`.

## Review Focus

- **Forged/duplicate TrxID:** a customer must not be able to make an order look paid, reuse another order's TrxID, or submit a second payment while one is pending.
- **Credential stuffing:** repeated failed logins from one IP/email pair must be throttled without locking out legitimate users who succeed.
- **Stolen session after password change:** the old remember-me token and other sessions must stop working.
- **Proxy/TLS deployment:** secure cookies, HSTS and client-IP throttling must behave correctly behind Railway's TLS terminator.
- **Queued email:** verification mail must actually be delivered by a running worker, not left in the `jobs` table forever.
- **CSP compatibility:** the policy must allow Bootstrap/Google Fonts/inline storefront scripts while blocking third-party script origins.

---

## File Structure

**Create**
- `docker/start.sh` — boot migrations/caches, then hand off to supervisord.
- `docker/supervisord.conf` — app server + queue worker processes.
- `.dockerignore` — keep build context small and secret-free.
- `.env.production.example` — production-only safe defaults.
- `.github/workflows/ci.yml` — tests, audit, Docker build.
- `.github/dependabot.yml` — dependency update automation.
- `SECURITY.md` — disclosure policy and supported versions.
- `docs/security/2026-10-23-audit-remediation.md` — finding-by-finding remediation record.
- `tests/Feature/HttpSecurityTest.php` — headers/CSP/HSTS.
- `tests/Feature/RateLimitTest.php` — auth/abuse throttles.
- `tests/Feature/PasswordSecurityTest.php` — session/remember-token invalidation.
- `tests/Feature/PaymentVerificationTest.php` — reserve/verify/reject workflow.
- `database/migrations/2026_10_23_000001_add_payment_verification_to_orders_table.php`
- `database/migrations/2026_10_23_000002_add_hot_path_indexes.php`

**Modify**
- `composer.json` / `composer.lock` — Laravel 12, remove Heroku buildpack, enable audit blocking.
- `Dockerfile` — PHP 8.4, Composer image, worker, non-root processes, healthcheck.
- `.env.example`, `config/session.php`, `bootstrap/app.php`.
- `app/Http/Middleware/SecurityHeaders.php`.
- `app/Providers/AppServiceProvider.php` — named rate limiters.
- `app/Http/Controllers/Auth/LoginController.php`, `PasswordController.php`.
- `app/Http/Controllers/PaymentController.php`, `Admin/OrderController.php`.
- `app/Models/Order.php`.
- `routes/web.php`.
- `resources/views/{checkout/payment,orders/index,orders/show,admin/orders/index,admin/orders/show,admin/dashboard}.blade.php`.
- `database/seeders/DatabaseSeeder.php`.
- `README.md`, `CODE-REVIEW.md`, `e2e/cart-checkout.spec.ts`.

---

### Task 1: Framework and dependency remediation

**Files:**
- Modify: `composer.json`
- Regenerate: `composer.lock`
- Test: existing `tests/`

**Interfaces:**
- Produces: `laravel/framework:^12.0`, `config.audit.block-insecure=true`, no `heroku/heroku-buildpack-php`.

- [ ] **Step 1: Edit composer.json**

Set `"laravel/framework": "^12.0"`, remove the `heroku/heroku-buildpack-php` line, and set `"block-insecure": true` under `config.audit`.

- [ ] **Step 2: Update the lock file**

Run: `composer update --with-all-dependencies --no-interaction`
Expected: resolves Laravel 12.69.0+ and patched Guzzle/CommonMark/Flysystem/Symfony releases.

- [ ] **Step 3: Verify advisories are gone**

Run: `composer audit`
Expected: `No security vulnerability advisories found.`

- [ ] **Step 4: Run the existing suite**

Run: `php artisan test --compact`
Expected: all existing tests pass. If the Laravel 12 upgrade changed the kernel middleware-group API used by `SecurityAuditTest`, update that test to inspect `app('router')->getMiddlewareGroups()` (or route middleware) without weakening the assertion.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock
git commit -m "build: upgrade Laravel to 12 and remediate dependency advisories"
```

---

### Task 2: Runtime, container and environment hardening

**Files:**
- Modify: `Dockerfile`, `.env.example`, `config/session.php`, `bootstrap/app.php`
- Create: `docker/start.sh`, `docker/supervisord.conf`, `.dockerignore`, `.env.production.example`

**Interfaces:**
- Produces: PHP 8.4 container, `php artisan queue:work` supervised alongside `php artisan serve`, trusted-proxy configuration from `TRUSTED_PROXIES`, production-secure session defaults.

- [ ] **Step 1: Dockerfile**

Base on `php:8.4-cli`. Install `libzip-dev unzip git curl gosu supervisor`. Enable `pdo pdo_mysql zip opcache bcmath`. Copy Composer from `composer:2`. Add opcache/`expose_php=0`/`memory_limit=256M` ini settings. Install dependencies with `--no-dev`. Copy `docker/start.sh` and `docker/supervisord.conf`. Expose 8080, add a `HEALTHCHECK` against `/up`, and set the entrypoint to the start script.

- [ ] **Step 2: Boot script**

`docker/start.sh` must (as root): restore seed media non-destructively, create writable directories, `chown` `storage` and `bootstrap/cache` to `www-data`, run `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`, then `exec supervisord -c /etc/supervisor/conf.d/sordar.conf`.

- [ ] **Step 3: Supervisor**

`docker/supervisord.conf` runs two programs as `www-data`, both with `autorestart=true` and stdout/stderr logging:
- `app`: `php artisan serve --host=0.0.0.0 --port=%(ENV_PORT)s`
- `queue`: `php artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600`

- [ ] **Step 4: Environment defaults**

In `.env.example`: `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE` documented with a production note, `TRUSTED_PROXIES` documented, `ALLOW_DEMO_SEED=false`. Create `.env.production.example` with `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `TRUSTED_PROXIES=*`, `ALLOW_DEMO_SEED=false`, and placeholders for DB/mail/secrets. Never add a real key.

- [ ] **Step 5: Session/proxy wiring**

`config/session.php`: default `secure` to true when `APP_ENV=production`, default `encrypt` to true. `bootstrap/app.php`: call `$middleware->trustProxies(at: env('TRUSTED_PROXIES'))` when the variable is set, and append `Illuminate\Session\Middleware\AuthenticateSession` to the `web` group.

- [ ] **Step 6: Verify**

Run: `php -l docker/start.sh` is not applicable; run `php artisan config:show session` and `php artisan test --compact`. Expected: suite green and production defaults visible.

- [ ] **Step 7: Commit**

```bash
git add Dockerfile docker .dockerignore .env.example .env.production.example config/session.php bootstrap/app.php
git commit -m "build: harden container and secure production environment defaults"
```

---

### Task 3: CSP/HSTS transport headers

**Files:**
- Modify: `app/Http/Middleware/SecurityHeaders.php`
- Test: `tests/Feature/HttpSecurityTest.php`

**Interfaces:**
- Produces: every response carries `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`, `Cross-Origin-Resource-Policy`, `X-Permitted-Cross-Domain-Policies`; secure production responses also carry `Strict-Transport-Security`.

- [ ] **Step 1: Write the failing tests**

```php
public function test_csp_allows_storefront_assets_but_blocks_arbitrary_scripts(): void
public function test_hsts_is_only_emitted_for_secure_production_requests(): void
```

Assert `default-src 'self'`, jsDelivr in `script-src`, Google Fonts in `style-src`/`font-src`, `object-src 'none'`, `frame-ancestors 'self'`; assert HSTS absent on HTTP/testing and present with `max-age=31536000` when `config(['app.env' => 'production'])` and HTTPS is forced.

- [ ] **Step 2: Run tests to verify failure**

Run: `php artisan test --filter=HttpSecurityTest`
Expected: FAIL — CSP/HSTS headers missing.

- [ ] **Step 3: Implement**

Build the CSP in the middleware. Allow `'unsafe-inline'` for script/style (existing inline storefront JS and style attributes), `https://cdn.jsdelivr.net`, `https://fonts.googleapis.com`, `https://fonts.gstatic.com`, `data:` images, `https:` images, `connect-src 'self'`. Add `upgrade-insecure-requests` only when the request is secure. Emit HSTS only when `$request->isSecure() && config('app.env') === 'production'`.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=HttpSecurityTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/SecurityHeaders.php tests/Feature/HttpSecurityTest.php
git commit -m "security: add CSP and conditional HSTS transport headers"
```

---

### Task 4: Authentication and abuse rate limiting

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`, `routes/web.php`, `app/Http/Controllers/Auth/LoginController.php`
- Test: `tests/Feature/RateLimitTest.php`

**Interfaces:**
- Produces: named limiters `login`, `register`, `password-email`, `password-reset`, `suggestions`, `community`, `payment`; login also enforces 5 failed attempts per email+IP and clears on success.

- [ ] **Step 1: Write failing tests**

```php
public function test_sixth_failed_login_for_same_email_and_ip_is_throttled(): void
public function test_successful_login_clears_the_login_limiter(): void
public function test_registration_is_rate_limited(): void
public function test_password_reset_requests_are_rate_limited(): void
public function test_suggestions_endpoint_is_rate_limited(): void
```

Use distinct `REMOTE_ADDR` values where a test must avoid cross-test limiter pollution.

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --filter=RateLimitTest`
Expected: FAIL — throttling absent.

- [ ] **Step 3: Implement limiters**

In `AppServiceProvider::boot()` register:
- `login`: 20/min per IP (route defense)
- `register`: 10/hour per IP
- `password-email`: 3/min per email+IP and 10/min per IP
- `password-reset`: 6/min per IP
- `suggestions`: 60/min per IP
- `community`: 5/min per user/IP
- `payment`: 6/min per user/IP

Attach `throttle:…` to the corresponding POST/GET routes. In `LoginController::store()` add the per-email+IP `RateLimiter` check (5 attempts, 60s decay), `hit()` on failure, `clear()` on success, and normalize the email to lowercase before `Auth::attempt()`.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=RateLimitTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Providers/AppServiceProvider.php routes/web.php app/Http/Controllers/Auth/LoginController.php tests/Feature/RateLimitTest.php
git commit -m "security: rate-limit login, registration, password reset and abuse-prone endpoints"
```

---

### Task 5: Password-change session invalidation

**Files:**
- Modify: `app/Http/Controllers/Auth/PasswordController.php`
- Test: `tests/Feature/PasswordSecurityTest.php`

**Interfaces:**
- Produces: password change rotates `remember_token`, calls `Auth::logoutOtherDevices($newPassword)`, and regenerates the current session; password-reset requests no longer reveal whether an email exists.

- [ ] **Step 1: Write failing tests**

```php
public function test_password_change_rotates_remember_token_and_invalidates_other_sessions(): void
public function test_forced_password_change_does_not_require_the_temporary_password(): void
public function test_forgot_password_does_not_reveal_unknown_email_addresses(): void
```

For the session test, capture the old token and assert it changed; assert the current session remains authenticated.

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --filter=PasswordSecurityTest`
Expected: FAIL — token unchanged / unknown email reveals error.

- [ ] **Step 3: Implement**

Use `forceFill(['password' => $request->password, 'must_change_password' => false, 'remember_token' => Str::random(60)])->save()`, then `Auth::logoutOtherDevices($request->password)` and `$request->session()->regenerate()`. In `sendResetLink()` return the same success message for unknown addresses; surface only `RESET_THROTTLED` as an error.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=PasswordSecurityTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/PasswordController.php tests/Feature/PasswordSecurityTest.php
git commit -m "security: invalidate sessions and rotate remember tokens on password change"
```

---

### Task 6: Payment reserve/verify/reject workflow

**Files:**
- Modify: `app/Models/Order.php`, `app/Http/Controllers/PaymentController.php`, `app/Http/Controllers/Admin/OrderController.php`, `routes/web.php`, six Blade views, `tests/Feature/ShopFlowTest.php`
- Create: `database/migrations/2026_10_23_000001_add_payment_verification_to_orders_table.php`, `tests/Feature/PaymentVerificationTest.php`

**Interfaces:**
- Produces:
  - `Order::PAYMENT_UNPAID|PAYMENT_PAID|PAYMENT_FAILED`
  - `Order::isAwaitingVerification(): bool`
  - `Order::paymentStatusLabel(): string`
  - `Order::paymentStatusColor(): string`
  - `PATCH /admin/orders/{order}/payment/verify` → `admin.orders.payment.verify`
  - `PATCH /admin/orders/{order}/payment/reject` → `admin.orders.payment.reject`
  - `payment_submitted_at`, `payment_verified_at`, `payment_verified_by` columns; unique `transaction_id`.

- [ ] **Step 1: Migration**

Add nullable `payment_submitted_at`, `payment_verified_at`, and `payment_verified_by` (FK users, nullOnDelete). Add a unique index on `transaction_id` and an index on `['status','payment_status']`. Down reverses them.

- [ ] **Step 2: Order model**

Add the constants/accessors above, cast the new timestamps to `datetime`, and add the new columns to `$fillable`. `isAwaitingVerification()` is true when `payment_status === unpaid` and `transaction_id` is non-null.

- [ ] **Step 3: Write failing tests**

```php
public function test_submitting_a_transaction_id_reserves_stock_and_awaits_verification(): void
public function test_admin_can_verify_a_pending_payment(): void
public function test_admin_can_reject_a_pending_payment_and_stock_is_restored(): void
public function test_customer_can_resubmit_after_rejection(): void
public function test_customer_cannot_resubmit_while_payment_is_pending(): void
public function test_transaction_ids_are_normalized_to_uppercase(): void
public function test_non_admin_cannot_verify_or_reject_payments(): void
```

Update `ShopFlowTest` so successful submission asserts `unpaid` + `payment_submitted_at` + reserved stock, not `paid`.

- [ ] **Step 4: Run to verify failure**

Run: `php artisan test --filter='PaymentVerificationTest|ShopFlowTest'`
Expected: FAIL.

- [ ] **Step 5: Implement the customer flow**

In `PaymentController`:
- Redirect paid orders.
- Redirect already-pending orders to the order page with an info flash.
- Normalize the TrxID to uppercase/trimmed; validate `regex:/^[A-Za-z0-9]+$/` and uniqueness ignoring this order.
- Inside `DB::transaction`, lock the order row; re-read each variant `lockForUpdate`; throw `RuntimeException('insufficient_stock')` if short; decrement; set `payment_status=unpaid`, `transaction_id`, `payment_submitted_at=now()`, clear verification fields; delete the cart.
- Catch `QueryException` for the unique index and show the duplicate-TrxID error; catch stock errors with the existing friendly message.
- Keep the post-payment depletion notification reset.

- [ ] **Step 6: Implement admin verify/reject**

`verifyPayment`: only pending orders; set `paid`, `payment_verified_at`, `payment_verified_by`; redirect with success.
`rejectPayment`: transactionally lock the order and variants, increment stock by each item quantity, set `payment_status=failed`, keep the TrxID for audit, stamp verifier; after commit run `InventoryService::handleRestock()` for affected products. Reject non-pending orders with an error.

- [ ] **Step 7: Update UI**

- Payment page: “Payment submitted. We will verify your transaction.” (already partially present) and no claim of automatic confirmation.
- Customer order index/show: use `paymentStatusLabel()/Color()`; pending orders show “Awaiting verification”, failed show “Payment failed”, and only unpaid-with-no-TrxID shows “Pay now”.
- Admin order index/show/dashboard: use the helpers; add Verify/Reject buttons on pending orders plus verifier/timestamp audit text.
- Routes: add the two PATCH routes inside the admin group.

- [ ] **Step 8: Run tests**

Run: `php artisan test --filter='PaymentVerificationTest|ShopFlowTest|AdminOrderTest'`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Models/Order.php app/Http/Controllers/PaymentController.php app/Http/Controllers/Admin/OrderController.php routes/web.php resources/views tests/Feature/PaymentVerificationTest.php tests/Feature/ShopFlowTest.php database/migrations/2026_10_23_000001_add_payment_verification_to_orders_table.php
git commit -m "feat: require admin verification of self-reported payments"
```

---

### Task 7: Hot-path database indexes

**Files:**
- Create: `database/migrations/2026_10_23_000002_add_hot_path_indexes.php`

**Interfaces:**
- Produces: indexes for catalogue filters, order lists, wishlist/community moderation, and care-guide publishing.

- [ ] **Step 1: Migration**

Add indexes (guarding against duplicates using `Schema::hasIndex` where supported):
`products(seller_id)`, `products(is_featured)`, `product_variants(stock)`, `product_variants(price)`, `orders(user_id, created_at)`, `orders(status, payment_status)`, `community_submissions(status)`, `care_guides(published_at)`, `users(role, is_active)`.

- [ ] **Step 2: Run the suite**

Run: `php artisan test --compact`
Expected: migrations apply cleanly on SQLite and all tests pass.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_10_23_000002_add_hot_path_indexes.php
git commit -m "perf: index catalogue, order and moderation hot paths"
```

---

### Task 8: Production seeding and demo-account guard

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`, `.env.example`
- Test: existing seeder usage (manual verification)

- [ ] **Step 1: Implement guard**

In `DatabaseSeeder::run()`, return early with a warning when `app()->environment('production')` unless `env('ALLOW_DEMO_SEED')` is true. Keep local/testing seeding unchanged.

- [ ] **Step 2: Verify**

Run: `php artisan migrate:fresh --seed` in local testing and `php artisan test --compact`.
Expected: local seeding works; suite green.

- [ ] **Step 3: Commit**

```bash
git add database/seeders/DatabaseSeeder.php .env.example
git commit -m "security: prevent demo credentials from being seeded in production"
```

---

### Task 9: CI, Dependabot and documentation

**Files:**
- Create: `.github/workflows/ci.yml`, `.github/dependabot.yml`, `SECURITY.md`, `docs/security/2026-10-23-audit-remediation.md`
- Modify: `README.md`, `CODE-REVIEW.md`, `e2e/cart-checkout.spec.ts`, `phpunit.xml` if needed

- [ ] **Step 1: CI**

GitHub Actions on push/PR to `main`:
- `tests` job: Ubuntu, PHP 8.4, `composer install`, copy `.env.example`, `php artisan key:generate`, `composer audit`, `php artisan test --compact`.
- `docker` job: `docker build -t sordar-agro:ci .`.
Set `permissions: contents: read`.

- [ ] **Step 2: Dependabot**

Weekly Composer, npm and GitHub Actions updates, grouped patch/minor updates.

- [ ] **Step 3: Documentation**

- README: Laravel 12, PHP 8.4 container / 8.2 minimum, queue worker, production env file, payment verification workflow, CI badge/link.
- CODE-REVIEW: append the new remediation section (do not rewrite history).
- SECURITY.md: supported versions, private disclosure, scope.
- Audit remediation doc: table mapping S-01…S-09 + dependency advisories to the code that fixes each, with verification commands.

- [ ] **Step 4: E2E spec**

Update `e2e/cart-checkout.spec.ts` so the full journey asserts the flash “submitted/awaiting verification” and the “Awaiting verification” badge, and that the payment page redirects to the order detail (not “paid”).

- [ ] **Step 5: Verify**

Run:
```bash
composer audit
php artisan test --compact
vendor/bin/pint --test app routes tests database/migrations
```
Expected: audit clean, tests green, Pint reports only pre-existing style debt (do not mass-format unrelated files).

- [ ] **Step 6: Commit**

```bash
git add .github SECURITY.md docs/security README.md CODE-REVIEW.md e2e/cart-checkout.spec.ts
git commit -m "ci: add audit/test/docker pipeline and document remediation"
```

---

### Task 10: Final verification, branch and push

- [ ] **Step 1: Full verification**

Run: `composer validate --strict`, `composer audit`, `php artisan test --compact`, `php artisan route:list --except-vendor`.
Expected: no advisories, all tests green, routes include the new admin payment actions.

- [ ] **Step 2: Inspect the diff**

Run: `git status --short` and `git diff --stat origin/main...HEAD`.
Expected: no `.env`, vendor, runtime or generated files; only intended changes.

- [ ] **Step 3: Branch and commit**

Create `security/audit-fixes-2026-10`, ensure all changes are committed with the logical commits above. Configure a repo-local git identity only if none exists.

- [ ] **Step 4: Push**

Run: `git push -u origin security/audit-fixes-2026-10`
If authentication is unavailable, report the exact failure and leave the branch ready to push; do not force-push or push to `main`.

---

## Verification Checklist

- [ ] `composer audit` → zero advisories.
- [ ] `php artisan test --compact` → all tests pass.
- [ ] `composer validate --strict` → valid.
- [ ] `git diff --check` → no whitespace errors.
- [ ] `docker build` validated by CI (or documented as not runnable locally if Docker is unavailable).
- [ ] No secrets in the diff (`git diff origin/main...HEAD | Select-String -Pattern 'APP_KEY=|password='` reviewed manually).
- [ ] Branch pushed; PR URL reported to the user.
