# Sordar Agro — Code Review & Fixes Applied

This document records a code review of the project and the fixes applied in this
reviewed copy. It is written honestly: the project is a **CSE412 academic Laravel
application**, not a commercial product, so the review targets correctness, basic
security, and clean Laravel practice — not unnecessary production infrastructure.

---

## What was reviewed
Controllers, models, routes, middleware, migrations, the service layer, Blade views,
the cart/checkout/payment flow, and the factory/test setup.

## Overall assessment
Architecturally sound for its scope: a standard Laravel 12 MVC monolith with a thin
service layer. Mass assignment is guarded (`$fillable` everywhere), passwords are
hashed via cast, order line items are snapshotted, and stock changes run in a
transaction. The main weaknesses are the absence of Form Requests, Policies, and real
test coverage — all normal next steps rather than defects.

---

## Defects found and FIXED in this copy

### 1. [HIGH] Stock could be oversold / driven negative at payment time
**File:** `app/Http/Controllers/PaymentController.php`
**Problem:** stock was validated at checkout but decremented later at payment, with no
re-check. If stock fell between the two steps, `decrement()` on an unsigned column could
go negative (a database error) and the system could oversell.
**Fix:** re-validate every item's stock before taking payment; inside the transaction,
re-read each variant with `lockForUpdate()` and roll back rather than oversell; failures
are shown to the user gracefully instead of crashing.

### 2. [MEDIUM] N+1 queries in the payment loops
**File:** `app/Http/Controllers/PaymentController.php`
**Problem:** `$order->items` then `$item->variant` / `->product` lazily loaded one query
per item.
**Fix:** `$order->loadMissing('items.variant.product')` once up front.

### 3. [MEDIUM] Tests could not run — missing `CategoryFactory`
**Files:** `database/factories/ProductFactory.php` (referenced `Category::factory()`),
no `CategoryFactory` existed.
**Fix:** added `CategoryFactory` and `ProductVariantFactory`, plus a `withVariant()`
helper on `ProductFactory`, so factory-based tests work on a fresh database.

---

## Tests added (`tests/Feature/`)
- **AuthTest** — public registration always yields a `customer` (privilege-escalation
  attempt ignored); role-based post-login redirects; suspended users blocked.
- **ShopFlowTest** — out-of-stock cannot be carted; add-to-cart works; submitting a TrxID
  reserves stock and clears the cart (since the 2026-10 hardening, stock is reserved at
  submission and admin-verified rather than decremented on self-report); **payment is refused when stock dropped below the
  ordered quantity** (regression test for defect #1).
- **AuthorizationTest** — guests redirected from cart; users cannot view others' orders;
  customers cannot reach admin or seller areas.

> Run them with `php artisan test`. They were written against the documented behavior;
> verify locally and report any failures.

---

## Recommended next steps (NOT applied, to avoid unverified large refactors)
These are the highest-value improvements a reviewer would expect next. They are
described rather than force-applied because they touch many files and should be made
with the app running so each step can be tested:

1. **Form Requests** — move inline `$request->validate()` into dedicated request classes
   (e.g. `StoreProductRequest`), removing duplicated validation between `store`/`update`.
2. **Policies** — replace the repeated `abort_unless($x->user_id === auth()->id(), 403)`
   checks with an `OrderPolicy`, `CartPolicy`, etc., registered and called via
   `$this->authorize()`.
3. **Thin the controllers** — move order assembly and variant persistence into action or
   service classes so business rules live in the domain layer.

## Intentionally out of scope (academic prototype)
Real payment/courier integrations and horizontal scaling. Payment verification is
manual by design (an admin checks the bKash/Nagad transfer), couriers are simulated,
and the Docker deployment targets a single small container.

---

## 2026-10 production hardening follow-up (APPLIED)

A second review focused on production/security readiness. The full findings and
fixes are in `docs/security/2026-10-23-audit-remediation.md`; the main changes are:

- Upgraded to **Laravel 12** and PHP **8.4** in the container, with a PHP 8.2/8.3/8.4
  CI matrix.
- Added rate limiting for login, registration, password resets, suggestions,
  community submissions, and payments.
- **Payment verification:** a self-reported TrxID no longer marks an order paid. Stock
  is reserved on submission, an admin must verify the transfer, and rejection or
  cancellation releases the stock. A database unique index closes the concurrent
  TrxID race.
- Password changes rotate `remember_token`, invalidate other sessions, and regenerate
  the session; `AuthenticateSession` is enabled globally.
- Added CSP, HSTS (HTTPS + production), COOP/CORP, and related security headers.
- Hardened environment defaults, added `.env.production.example` and trusted-proxy
  support, and made demo seeding refuse to run in production.
- Added a Supervisor-managed queue worker, container healthcheck, hot-path indexes,
  CI, Dependabot, and `SECURITY.md`.
- The test suite grew to **348 tests / 918 assertions**, including dedicated
  payment-verification, rate-limit, password-session, header, and production-seeding
  tests.
