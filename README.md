# Sordar Agro — Online Aquarium Marketplace

A Laravel 11 web application built for **CSE412 (Software Engineering )** .
Sordar Agro is an online marketplace for aquarium hobbyists to buy fish (sold in pairs),
aquatic plants, fish food, and equipment, with seller and admin workspaces, a simulated
payment/courier flow, care guides, and a community knowledge board.

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
- **Stock is decremented only after a successful (simulated) payment.**
- **Restock notifications**: when an admin/seller increases stock on an out-of-stock
  product, everyone who wishlisted it receives a "back in stock" notification. With
  `MAIL_MAILER=log`, these are written to `storage/logs/laravel.log` (no SMTP needed).
- **Payments (bKash / Nagad) and couriers (Pathao / Steadfast) are simulated** for the
  academic scope — a button stands in for the real provider callback.

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

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sordar_agro
DB_USERNAME=root
DB_PASSWORD=
```

**SQLite (quickest for testing):**

```
DB_CONNECTION=sqlite
```

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
   temperament, and availability filters, plus keyword search.
2. **Register** a customer, open a fish product, switch sizes, add a pair to the cart.
3. **Checkout** → choose bKash/Nagad → **simulate a successful payment**. Watch stock drop
   and the order appear under *My Orders* with tracking.
4. Log in as **admin** → *Inventory* → increase stock on an out-of-stock product to trigger
   a restock email (check `storage/logs/laravel.log`).
5. Log in as the **seller** → add a product → log back in as **admin** → *Product Approvals*
   → approve/reject with feedback.
6. As admin, open an order → set status to *shipped*, assign a courier → a tracking code is
   auto-generated.
7. Submit a **community** post as a customer, then approve it as admin.

---

## Running tests

```bash
php artisan test
```

A minimal PHPUnit scaffold is included (`tests/Feature`, `tests/Unit`). Tests run against an
in-memory SQLite database (configured in `phpunit.xml`).

---

## Project structure (high level)

```
app/
  Http/Controllers/        Auth, storefront, cart, wishlist, checkout, payment,
                           orders, care, community, Seller/*, Admin/*
  Http/Middleware/         RoleMiddleware, EnsurePasswordChanged
  Models/                  11 Eloquent models
  Services/                InventoryService (restock notifications)
database/
  migrations/              13 migrations
  seeders/                 User, Category, Product, CareGuide seeders
  factories/               User & Product factories
resources/views/           Blade views (layouts, partials, storefront, dashboards)
routes/web.php             All named routes, grouped by role/middleware
public/css/app.css         Ocean/aquarium theme
```

---

## Academic honesty note

This project was scaffolded as a learning deliverable for CSE412. Read through the code,
run it, test each feature, and extend it — treat it as a starting point you understand and
can defend, not a black box to submit unread.

---

*Group 05 · Section 02 · CSE412 Software Engineering*
