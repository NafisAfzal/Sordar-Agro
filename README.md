# Sordar Agro — Online Aquarium Marketplace

Sordar Agro is an online marketplace for aquarium hobbyists to buy fish (sold in pairs),
aquatic plants, fish food, and equipment, with seller and admin workspaces, a bKash/Nagad
payment flow, simulated courier tracking, care guides, and a community knowledge board.

> This is a runnable Laravel source tree, not a pre-built binary. You run `composer install`
> and the migrations/seeders locally as described below.

---

## Tech stack

- **Laravel 11** (PHP 8.2+), Blade templating
- **Bootstrap 5** + Bootstrap Icons via CDN (no npm / Vite build step required)
- **MySQL** (default) — SQLite also works for quick local testing
- Hand-rolled authentication (no Breeze/Jetstream) with role-based middleware

---

## Roles

| Role | How it's created | Capabilities |
|------|------------------|--------------|
| **Customer** | Self-registers at `/register` | Browse, wishlist, cart, checkout, track orders, contribute to community |
| **Partner Seller** | Provisioned by an admin (forced password change on first login) | Everything a customer can do **plus** list/manage products (subject to admin approval) |
| **Administrator** | Seeded only — never creatable through the UI | Approve/reject products, manage inventory, orders & couriers, users, sellers, care guides, community |

---

## Domain rules worth knowing

- **Fish are sold as pairs**: one unit of stock = 2 fish. Fish products have three size
  variants (small / medium / large), each with its own price, stock, and description.
- **Non-fish products** (plants, food, equipment) use a single `standard` variant.
- **Stock is decremented only after a successful payment.**
- **Restock notifications**: when an admin/seller increases stock on an out-of-stock
  product, everyone who wishlisted it receives a "back in stock" notification. With
  `MAIL_MAILER=log`, these are written to `storage/logs/laravel.log` (no SMTP needed).
- **Payments are confirmed via customer-submitted Transaction ID** — the customer sends
  money through their bKash/Nagad app and enters the resulting TrxID, which the system
  validates for uniqueness and confirms the order against. This is not a live gateway API
  integration, so payments are not automatically verified against bKash/Nagad's systems.
- **Couriers (Pathao / Steadfast) are simulated** for the academic scope — a courier is
  assigned and a tracking code generated without a real courier API integration.
- **Live search suggestions** appear in the navbar as the customer types a product name.

---

## Local setup

### Prerequisites
- PHP **8.2+** with extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- **Composer**
- **MySQL** (or use SQLite — see note below)

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

## Feature walkthrough (suggested demo path)

1. **Browse & filter** the shop (`/products`) — try the category, price, tank-size,
   temperament, and availability filters, plus live search suggestions as you type.
2. **Register** a customer, open a fish product, switch sizes, add a pair to the cart.
3. **Checkout** → choose bKash or Nagad → you'll land on a payment page showing the
   payment number and a QR code. Send the amount via your bKash/Nagad app, then submit
   the resulting Transaction ID to confirm the order and watch stock drop.
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

As of this writing the suite has **34 tests (50 assertions)**, all passing, run against an
in-memory SQLite database (configured in `phpunit.xml`). Six tests emit a harmless
`BigNumber::of()` float-cast deprecation notice from a decimal-cast dependency — cosmetic,
not a functional issue.

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
- **`ShopFlowTest`** — out-of-stock variants can't be added to cart, in-stock ones can, a successful payment decrements stock and clears the cart, duplicate Transaction IDs are rejected, **a stock-oversell regression test** (if stock drops below the ordered quantity between checkout and payment, payment is blocked rather than allowed to overdraw inventory), and **a min-price filter regression test** (a product must match the shop's price filter by its displayed starting price, not merely by having some variant in range — see Regression testing below).

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
- **Payment confirmation is manual, not gateway-integrated**: customers self-report a bKash/Nagad
  Transaction ID after sending money via their own app; the system checks it for uniqueness but
  does not call a live bKash/Nagad API to verify the transaction actually occurred.
- **Courier tracking is simulated**: a courier and tracking code are assigned for demonstration
  purposes, without integrating a real Pathao/Steadfast API.
- **Minor test noise**: 6 of the 34 tests emit a deprecation notice from a decimal-cast dependency
  (`BigNumber::of()` being passed a float). It doesn't affect correctness or pass/fail status, just
  a warning worth cleaning up eventually by casting to string first.

---

## Project structure (high level)
app/
Http/Controllers/ Auth, storefront, cart, wishlist, checkout, payment,
orders, care, community, Seller/, Admin/
Http/Middleware/ RoleMiddleware, EnsurePasswordChanged
Models/ 11 Eloquent models
Services/ InventoryService (restock notifications)
database/
migrations/ 13 migrations
seeders/ User, Category, Product, CareGuide seeders
factories/ User & Product factories
resources/views/ Blade views (layouts, partials, storefront, dashboards)
routes/web.php All named routes, grouped by role/middleware
public/css/app.css Ocean/aquarium theme

---

*Developed as part of the CSE412 (Software Engineering) course.*