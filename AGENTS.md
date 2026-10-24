# Agent Guidance: Sordar Agro

High-signal context for agents working in this repository.

## Architecture & Toolchain
- **Framework**: Laravel 12 (PHP 8.2+ locally; the Docker image runs PHP 8.4).
- **No Build Step**: Uses Bootstrap 5 and Icons via CDN. Do not look for `package.json` scripts or Vite/Mix config for CSS/JS changes. `package.json` exists only for Playwright E2E tests.
- **Custom Auth**: Hand-rolled authentication and role-based middleware (`customer`, `seller`, `admin`). No Breeze/Jetstream.
- **Role Routing**: Controllers are grouped in `app/Http/Controllers/` under `Admin/` and `Seller/` namespaces.
- **Queue**: Production runs `php artisan queue:work` under Supervisor (`docker/supervisord.conf`); tests use the `sync` driver.

## Domain Quirks
- **Fish Logic**: `is_fish` products must have three size variants (small/medium/large). 1 unit of stock = 1 pair (2 fish).
- **Non-Fish Logic**: Plants/equipment use a single `standard` variant.
- **Payment Flow**: Simulated/manual. Customers send money via bKash/Nagad and submit a TrxID. A TrxID is a **claim**, not proof: the order stays `unpaid` and shows **Awaiting verification** until an admin verifies it. Admin rejection marks it `failed`; the customer can retry with a new TrxID.
- **Stock Management**: Stock is **reserved at TrxID submission** in `PaymentController@process` under `lockForUpdate`, not at checkout and not at admin verification. Rejection and cancellation release stock. Never decrement twice for the same order.
- **Order States**: `payment_status` is `unpaid|paid|failed`; `unpaid` + non-null `transaction_id` means pending verification. Prefer the `Order::isPaid()`, `isAwaitingPaymentVerification()`, `paymentStatusLabel()`, and `paymentStatusColor()` helpers over raw string checks.
- **Notifications**: Restock emails are triggered via `app/Services/InventoryService.php` and written to `storage/logs/laravel.log` when `MAIL_MAILER=log`.

## Security Expectations
- Keep the named rate limiters in `AppServiceProvider` and the corresponding `throttle:` middleware on auth, payment, community, and suggestions routes.
- Do not remove `SecurityHeaders` (CSP/HSTS/COOP/CORP) or the `AuthenticateSession` middleware from the web group.
- Password changes must rotate `remember_token`, call `Auth::logoutOtherDevices()`, and regenerate the session.
- `DatabaseSeeder` must stay blocked in production unless `ALLOW_DEMO_SEED=true`.
- New user input that changes state needs validation, authorization, and a test. New dependencies need `composer audit` to stay clean.

## Development & Verification
- **Test Suite**: Run `php artisan test`. It uses an in-memory SQLite database (`phpunit.xml`). Safe to run without affecting local data.
- **Dependency checks**: `composer validate --strict`, `composer audit`, `npm audit --audit-level=high`.
- **E2E**: `npm ci`, then `npm run test:e2e` with the app running at `http://localhost:8000` and demo data seeded.
- **Seeding**: `php artisan migrate --seed` creates demo accounts for all roles (see `README.md`). Use a local/non-production environment.
- **Temporary Passwords**: Admin-provisioned sellers are forced to change passwords via `EnsurePasswordChanged` middleware.
- **Environment**: Ensure `storage:link` is run for product images to display.

## Workflow & Constraints
- **Branch Ownership**: Refer to `TEAM-OWNERSHIP.md` and `GIT-WORKFLOW.md`. Only push to the branch corresponding to the assigned teammate.
- **CI**: `.github/workflows/ci.yml` runs PHP 8.2/8.3/8.4 tests, Composer/npm audits, and a Docker build. Keep it green.
- **Manual Checklists**: See `REGRESSION-CHECKLIST.md` and `SMOKE-TEST-CHECKLIST.md` for manual verification steps that automated tests may not cover.
- **Security Docs**: `SECURITY.md` (policy/deployment checklist) and `docs/security/2026-10-23-audit-remediation.md` (audit findings and fixes).
- **Legacy Files**: `CODE-REVIEW.md` contains a log of previously fixed high-risk defects plus the 2026-10 hardening follow-up.
