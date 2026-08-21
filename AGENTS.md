# Agent Guidance: Sordar Agro

High-signal context for agents working in this repository.

## Architecture & Toolchain
- **Framework**: Laravel 11 (PHP 8.2+).
- **No Build Step**: Uses Bootstrap 5 and Icons via CDN. Do not look for `package.json` scripts or Vite/Mix config for CSS/JS changes.
- **Custom Auth**: Hand-rolled authentication and role-based middleware (`customer`, `seller`, `admin`). No Breeze/Jetstream.
- **Role Routing**: Controllers are grouped in `app/Http/Controllers/` under `Admin/` and `Seller/` namespaces.

## Domain Quirks
- **Fish Logic**: `is_fish` products must have three size variants (small/medium/large). 1 unit of stock = 1 pair (2 fish).
- **Non-Fish Logic**: Plants/equipment use a single `standard` variant.
- **Payment Flow**: Simulated. Customers submit a manual Transaction ID (bKash/Nagad). Verification is manual/visual by admin.
- **Stock Management**: Decremented only at payment time in `PaymentController@process` using `lockForUpdate` to prevent overselling.
- **Notifications**: Restock emails are triggered via `app/Services/InventoryService.php` and written to `storage/logs/laravel.log` (via `MAIL_MAILER=log`).

## Development & Verification
- **Test Suite**: Run `php artisan test`. It uses an in-memory SQLite database (`phpunit.xml`). Safe to run without affecting local data.
- **Seeding**: `php artisan migrate --seed` creates demo accounts for all roles (see `README.md`).
- **Temporary Passwords**: Admin-provisioned sellers are forced to change passwords via `EnsurePasswordChanged` middleware.
- **Environment**: Ensure `storage:link` is run for product images to display.

## Workflow & Constraints
- **Branch Ownership**: Refer to `TEAM-OWNERSHIP.md` and `GIT-WORKFLOW.md`. Only push to the branch corresponding to the assigned teammate.
- **Manual Checklists**: See `REGRESSION-CHECKLIST.md` and `SMOKE-TEST-CHECKLIST.md` for manual verification steps that automated tests may not cover.
- **Legacy Files**: `CODE-REVIEW.md` contains a log of previously fixed high-risk defects (like stock overselling).
