\# Sordar Agro — Smoke Test Checklist



Run this after EVERY deploy. Takes about 5 minutes. If anything fails,

do NOT consider the deploy successful — investigate immediately.



\## Basic availability

\- \[ ] Homepage loads (`/`) — no error page

\- \[ ] Shop page loads (`/products`) — products visible

\- \[ ] Login page loads (`/login`)



\## Authentication

\- \[ ] Can log in as customer@example.com

\- \[ ] Can log in as admin@example.com

\- \[ ] Can log in as seller@example.com



\## Core shopping flow

\- \[ ] Can add a product to cart

\- \[ ] Cart page shows the item

\- \[ ] Can reach checkout page

\- \[ ] Can reach payment page after submitting checkout form



\## Admin

\- \[ ] Admin dashboard loads with stats

\- \[ ] Product approvals page loads



\## Assets

\- \[ ] Site has correct styling (not plain black-and-white/unstyled)

\- \[ ] Images load (not broken image icons)



\## Result

Date: \_\_\_\_\_\_\_\_\_\_

Tested by: \_\_\_\_\_\_\_\_\_\_

Result: PASS / FAIL

Issues found: \_\_\_\_\_\_\_\_\_\_

