\# Sordar Agro — Regression Testing Checklist



Run this checklist after any change to filters, cart, or checkout.

Record PASS or FAIL and the date tested.



\## Filter combinations

\- \[ ] Category + Price range together — FAIL (minimum price filter did not work correctly)

\- \[ ] Category + Temperament + Availability together — PASS

\- \[ ] Search term + Category together — PASS

\- \[ ] Tank size + Availability together — PASS

\- \[ ] Price min greater than max (should show empty results, not an error) — PASS



\## Core flow

\- \[ ] Register a new account

\- \[ ] Log in

\- \[ ] Add item to cart

\- \[ ] Update cart quantity with +/- buttons

\- \[ ] Remove item from cart

\- \[ ] Complete checkout with a valid transaction ID

\- \[ ] Submitting the TrxID reserves stock and shows "Awaiting verification"

\- \[ ] Admin can verify the payment (order becomes paid)

\- \[ ] Admin rejection releases the reserved stock

\- \[ ] View order in order history

\- \[ ] Add item to wishlist while out of stock



\## Automated tests

\- \[ ] `php artisan test` — all tests pass

\- \[ ] `composer audit` — no advisories

\- \[ ] `npm audit --audit-level=high` — no high/critical advisories



Last run: 2026-08-18 by Mostahid — Result: FAIL (one filter test failed)

