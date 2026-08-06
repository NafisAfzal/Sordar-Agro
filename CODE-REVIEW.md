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
Architecturally sound for its scope: a standard Laravel 11 MVC monolith with a thin
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
- **ShopFlowTest** — out-of-stock cannot be carted; add-to-cart works; successful payment
  decrements stock and clears the cart; **payment is refused when stock dropped below the
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
Real payment/courier integrations, queues/Redis, Docker, CI/CD, and horizontal scaling.
These are correctly simulated or omitted for the course; "fixing" them would add
infrastructure the project does not need and cannot meaningfully demonstrate.
