# Sordar Agro — Online Aquarium Marketplace

Sordar Agro is an online marketplace for aquarium hobbyists to buy fish (sold in pairs),
aquatic plants, fish food, and equipment, with seller and admin workspaces, a bKash/Nagad
payment flow, simulated courier tracking, care guides, and a community knowledge board.

> This is a runnable Laravel source tree, not a pre-built binary. You run `composer install`
> and the migrations/seeders locally as described below.

---

## Tech stack

- **Laravel 12** (PHP 8.2+ locally; the Docker image runs PHP 8.4), Blade templating
- **Bootstrap 5** + Bootstrap Icons via CDN (no npm / Vite build step required)
- **MySQL** (default) — SQLite also works for quick local testing
- Hand-rolled authentication (no Breeze/Jetstream) with role-based middleware
- **PHPUnit** feature/unit tests + **Playwright** browser tests
- **GitHub Actions** CI (PHP 8.2/8.3/8.4, dependency audits, Docker build)

---

## Roles

| Role | How it's created | Capabilities |
|------|------------------|--------------|
| **Customer** | Self-registers at `/register` | Browse, wishlist, cart, checkout, track orders, contribute to community |
| **Partner Seller** | Provisioned by an admin (forced password change on first login) | Everything a customer can do **plus** list/manage products (subject to admin approval) and a **seller analytics dashboard** (gross sales, earnings, units, orders, product performance, low-stock alerts, date-filtered) |
| **Administrator** | Seeded only — never creatable through the UI | Approve/reject products, manage inventory, orders & couriers, users, sellers, care guides, community, **marketplace-wide analytics** (date-filtered revenue, own vs seller product breakdown, marketplace earnings, best-selling products) + **Needs Attention** operations snapshot |

---

## Domain rules worth knowing

- **Fish are sold as pairs**: one unit of stock = 2 fish. Fish products have three size
  variants (small / medium / large), each with its own price, stock, and description.
- **Non-fish products** (plants, food, equipment) use a single `standard` variant.
- **Stock is reserved when a Transaction ID is submitted.** If the payment is
  rejected or the order is cancelled, the reserved stock is released again and
  wishlist notifications can fire. Stock is never oversold.
- **Restock notifications**: when an admin/seller increases stock on an out-of-stock
  product, everyone who wishlisted it receives a "back in stock" notification. With
  `MAIL_MAILER=log`, these are written to `storage/logs/laravel.log` (no SMTP needed).
- **Payments are verified by an admin.** The customer sends money through their
  bKash/Nagad app and submits the resulting TrxID. That submission is treated as a
  claim, not proof: the order stays **unpaid / awaiting verification** until an admin
  clicks **Verify payment**. Rejecting releases the reserved stock. This is not a live
  gateway API integration, so admins still check the transfer in the bKash/Nagad app.
- **Rate limiting** protects login, registration, password resets, search suggestions,
  community submissions, and payment submissions.
- **Couriers (Pathao / Steadfast) are simulated** for the academic scope — a courier is
  assigned and a tracking code generated without a real courier API integration.
- **Live search suggestions** appear in the navbar as the customer types a product name.

---

## Local setup

### Prerequisites
- PHP **8.2+** with extensions: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `zip`
- **Composer**
- **MySQL** (or use SQLite — see note below)
- **Node 20+** only if you want to run the Playwright E2E suite

### Steps

```bash
# 1. Install PHP dependencies
composer install

# 2. Create your environment file
cp .env.example .env

# 3. Generate the application key
php artisan key:generate

# 4. Configure your database in .env (see below), then run migrations + seeders
php artisan migrate --seed

# 5. Link the storage directory (so uploaded images are served)
php artisan storage:link

# 6. Start the dev server
php artisan serve
```

Then open <http://127.0.0.1:8000>.

### Database configuration (`.env`)

**MySQL (default):** create a database, then set:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sordar_agro
DB_USERNAME=root
DB_PASSWORD=

**SQLite (quickest for testing):**

```bash
touch database/database.sqlite
php artisan migrate --seed
```

---

## Seeded demo accounts

| Role | Email | Password |
|------|-------|----------|
| Administrator | `admin@example.com` | `password` |
| Partner Seller | `seller@example.com` | `password` |
| Customer | `customer@example.com` | `password` |

The seeder also creates four categories, several fish products (each with three size
variants), a range of non-fish products, one **pending** seller product to demonstrate the
approval queue, and three published care guides.

> **Change these credentials** before any real deployment — they exist only for grading/demo.

---

## Production deployment

The repository ships a single-container `Dockerfile` (PHP 8.4) with Supervisor
running both the web process and a database queue worker. There is evidence of
deployment on Railway (a persistent volume comment and the `PORT` convention),
but **no Railway-specific configuration files are committed** — platform
settings live in your hosting dashboard, not in this repository. The container
does **not** use nginx/Apache/PHP-FPM; it serves via `php artisan serve` with a
small worker pool, which is appropriate for this project's scale.

### Startup behavior (Docker)

On boot the container performs these steps:

1. Restore the bundled seed media into `storage/app/public` (non-destructive `cp -rn`).
2. Give `www-data` write access to `storage` and `bootstrap/cache`.
3. Run `php artisan migrate --force`.
4. Build config/route/view caches as `www-data`.
5. Start Supervisor: the web server and `php artisan queue:work`.

A `/up` healthcheck is declared in the Dockerfile.

### Seeding

**Normal production startup does NOT run database seeders.**
`--seed` was intentionally removed from automatic startup: the seeders use
`updateOrCreate`, so running them against an existing database can reset demo
seller/customer passwords and overwrite seeded product prices, variant stock,
SKUs, categories, and care-guide content. Run seeders only deliberately in
development/demo environments (`php artisan migrate --seed`).

As a second line of defence, `DatabaseSeeder` **refuses to run when
`APP_ENV=production`** unless `ALLOW_DEMO_SEED=true` is explicitly set. Leave
`ALLOW_DEMO_SEED=false` on any real deployment.

### Required application environment

Supply these through your deployment environment — do not rely on `.env.example`
defaults, which are local-development values. `.env.production.example` is a
copy-paste starting point:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-production-domain
APP_KEY=<persistent-production-key>
LOG_LEVEL=warning
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*
ALLOW_DEMO_SEED=false
```

- `APP_KEY` **must be provided by you**: neither Docker startup nor Composer generates one.
  Generate a value once with `php artisan key:generate --show` and store it as an
  environment variable. It must remain stable across restarts/redeployments — rotating it
  invalidates sessions and encrypted data.
- With no `APP_KEY` set, the application fails at runtime ("No application encryption key").

### Database

Provide production database credentials exclusively as environment variables:
`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
Never commit production credentials to the repository.

### Mail

The example configuration uses `MAIL_MAILER=log`, which writes "emails" to
`storage/logs/laravel.log` and is suitable for **local development only**. Real
deployment must configure an actual mail provider (this project has used Brevo's HTTP
API) with that provider's own credentials/sender domain. Do not ship customer-facing
email on the `log` driver.

### Demo credentials in production

The demo accounts above are retained for grading/local demonstration only.
The production seeder guard (`ALLOW_DEMO_SEED=false`) prevents them from being
created on a real deployment, and any database copied from a demo environment
must have those accounts removed or their passwords changed before going live.

---

## Security

- **Reporting:** see [`SECURITY.md`](SECURITY.md) for private vulnerability
  reporting and the production deployment checklist.
- **Audit remediation:** see
  [`docs/security/2026-10-23-audit-remediation.md`](docs/security/2026-10-23-audit-remediation.md)
  for the full findings/fixes report (S-01 … S-09 + additional issues).
- **Headers:** CSP, HSTS (HTTPS + production), COOP/CORP, frame and MIME
  protections are emitted by `SecurityHeaders`.
- **Payments:** TrxIDs are admin-verified, unique at the database level, and
  reserve stock until approved or released.
- **Sessions:** encrypted by default, secure cookies in production, and other
  sessions are invalidated on password change.
- **CI:** `.github/workflows/ci.yml` runs the PHP test matrix, Composer/npm
  audits, and a Docker build on every push/PR to `main`; Dependabot checks
  Composer, npm, GitHub Actions, and Docker weekly.

---

## Feature walkthrough (suggested demo path)

1. **Browse & filter** the shop (`/products`) — try the category, price, tank-size,
   temperament, and availability filters, plus live search suggestions as you type.
2. **Register** a customer, open a fish product, switch sizes, add a pair to the cart.
3. **Checkout** → choose bKash or Nagad → you'll land on a payment page showing the
   payment number and a QR code. Send the amount via your bKash/Nagad app, then submit
   the resulting Transaction ID. Stock is reserved and the order shows **Awaiting
   verification**; log in as admin, open the order, and click **Verify payment**.
   Rejecting the payment releases the stock again.
4. Log in as **admin** → *Inventory* → increase stock on an out-of-stock product to trigger
   a restock email (check `storage/logs/laravel.log`).
5. Log in as the **seller** → add a product → log back in as **admin** → *Product Approvals*
   → approve/reject with feedback.
6. As admin, open an order → set status to *shipped*, assign a courier → a tracking code is
   auto-generated.
7. Submit a **community** post as a customer, then approve it as admin.

---

## Testing

Run the full suite with:

```bash
php artisan test
```

As of the 2026-10 hardening pass the suite has **347 tests (913 assertions)**,
all passing, run against an in-memory SQLite database (configured in
`phpunit.xml`).

Additional checks:

```bash
composer validate --strict
composer audit
npm ci && npm audit --audit-level=high
npm run test:e2e   # Playwright, needs a running app + seeded database
```

CI runs the tests on PHP 8.2, 8.3, and 8.4, plus the dependency audits and a
Docker build (`.github/workflows/ci.yml`).

### Unit tests (`tests/Unit/`)

Pure-PHP tests of model business logic, no database involved:

- **`OrderItemTest`** — `OrderItem::lineTotal()`: price × quantity, including a zero-quantity edge case.
- **`OrderStatusColorTest`** — `Order::statusColor()`: the Bootstrap badge colour returned for each order status (processing/shipped/delivered/cancelled) plus the fallback for an unrecognised status.
- **`ProductTest`** — `Product::getStartingPriceAttribute()` (minimum price across variants, and the single-variant case), `Product::getTotalStockAttribute()` (sum across variants including zero-stock ones), `Product::isOutOfStock()` (true when every variant is at zero, false when any variant has stock).
- **`CartTest`** — `Cart::subtotal()`: unit price × quantity.

### Feature tests (`tests/Feature/`)

Full HTTP-request tests against the application, exercising routes, middleware, and the database:

- **`AuthTest`** — registration always creates a customer role, role-based post-login redirects (customer → home, admin → dashboard), suspended users are blocked from logging in.
- **`AuthorizationTest`** — guests are redirected to login from the cart, a customer cannot view another customer's order, and role-gated routes (admin dashboard, seller workspace) reject customers.
- **`BoundaryValueTest`** *(Sayed)* — see Boundary value testing below.
- **`ShopFlowTest`** — out-of-stock variants can't be added to cart, in-stock ones can, submitting a TrxID reserves stock and clears the cart, duplicate Transaction IDs are rejected, **a stock-oversell regression test** (if stock drops below the ordered quantity between checkout and payment, reservation is blocked rather than allowed to overdraw inventory), and **a min-price filter regression test** (a product must match the shop's price filter by its displayed starting price, not merely by having some variant in range — see Regression testing below).
- **`PaymentVerificationTest`** — a submitted TrxID awaits admin verification; verify marks paid, reject releases stock, rejected payments can be retried, pending orders cannot be submitted twice, customers cannot call admin actions, and cancelling releases reserved stock only once.
- **`RateLimitTest`** — login (per IP and per email+IP), registration, password-reset, community, and payment throttles; limiter clears after a successful login; forgot-password does not reveal unknown emails.
- **`PasswordSecurityTest`** — password change rotates `remember_token`, invalidates sessions holding the old password digest, and still supports forced temporary-password changes.
- **`HttpSecurityTest`** — CSP/HSTS/COOP/CORP headers, including production-only HSTS on HTTPS.
- **`ProductionSeedingTest`** — demo accounts are not seeded in production unless `ALLOW_DEMO_SEED=true`.

### Regression testing

The full suite (`php artisan test`) was re-run after every merge and every change made this
sprint — each of the three teammate branch merges, the design-consistency fixes, and the new
unit tests were all verified against a clean run before being committed or pushed.

Mostahid also maintains a manual regression checklist (`REGRESSION-CHECKLIST.md`) covering
filter combinations and the core cart/checkout flow, run after any change to filters, cart, or
checkout. **Its run on 2026-08-18 caught a real bug**: the shop's minimum-price filter matched a
product if *any* one of its variants was in range, rather than the product's displayed starting
(cheapest-variant) price — so a product advertised well below the filtered minimum could still
appear in the results. This has since been fixed in `ProductController::index()` (the filter now
checks the cheapest variant, matching what the product card shows) and is covered by a new test,
`ShopFlowTest::test_min_price_filter_matches_starting_price_not_any_variant`. Everything else on
that checklist (temperament+availability, search+category, tank-size+availability, price-min-
greater-than-max, and the full register→cart→checkout→wishlist flow) passed.

### Black-box / user testing

Mostahid ran a black-box user testing session with **2 testers**, each working through three
tasks: register and buy a fish product, find and wishlist a product under ৳200, and attempt
checkout without an address (to confirm validation blocks it). **Total issues found: 0** — both
testers completed all tasks successfully, and the address-validation check behaved as expected.
The session did prompt two UX additions that shipped this sprint: the checkout step indicator
and disabling the submit button during order placement. See `USER-TESTING-LOG.md`.

### Boundary value testing

Sayed's `tests/Feature/BoundaryValueTest.php` targets the edges of two validated ranges:

- **Password length**: exactly 8 characters (the minimum) is accepted; 7 characters is rejected.
- **Cart quantity vs. stock**: requesting exactly the remaining stock is allowed; requesting more
  than the remaining stock is silently capped to the available amount rather than erroring.

### Smoke testing

Junaid's `SMOKE-TEST-CHECKLIST.md` is the post-deploy sanity check — basic page availability,
login for all three seeded roles, the cart→checkout→payment path, the admin dashboard, and that
styling/images are actually loading. It was run manually against the live Railway deployment
after this sprint's merge and all items passed.

---

## Known Limitations

- **Email deliverability**: transactional email (verification, restock notifications) is sent via
  Brevo's HTTP API using the default sender address configured for this project — the sending
  domain is not yet a verified custom domain. This affects spam-folder placement/deliverability
  on some providers, not functionality — emails still send successfully.
- **Payment confirmation is manual, not gateway-integrated**: customers self-report a
  bKash/Nagad Transaction ID after sending money via their own app. The system checks it
  for uniqueness, reserves stock, and requires an admin to verify that the transfer
  actually happened; it does not call a live bKash/Nagad API.
- **Courier tracking is simulated**: a courier and tracking code are assigned for demonstration
  purposes, without integrating a real Pathao/Steadfast API.
- **Minor test noise**: The test suite runs clean with no deprecation warnings in the current environment (SQLite returns decimal columns as strings). The `BigNumber::of()` float-cast deprecation from the decimal-cast dependency would only surface if float values were passed to decimal fields — all tests use integer or string values for prices.

---

## Project structure (high level)
app/
Http/Controllers/ Auth, storefront, cart, wishlist, checkout, payment,
orders, care, community, Seller/, Admin/
Http/Middleware/ RoleMiddleware, EnsurePasswordChanged, SecurityHeaders
Models/ 11 Eloquent models
Services/ InventoryService (restock notifications)
database/
migrations/ 15 migrations
seeders/ User, Category, Product, CareGuide seeders
factories/ User & Product factories
resources/views/ Blade views (layouts, partials, storefront, dashboards)
routes/web.php All named routes, grouped by role/middleware
public/css/app.css Ocean/aquarium theme
docker/ start.sh + supervisord.conf (web + queue worker)
.github/ CI workflow + Dependabot
docs/security/ Audit remediation report

---

*Developed as part of the CSE412 (Software Engineering) course.*