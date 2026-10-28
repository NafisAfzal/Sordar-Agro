# Phase 2 Implementation Plan — Updated Progress

## Critical First: Fix Boot (2.3 — IdleSession middleware) ✅ DONE
- [x] Created `app/Http/Middleware/IdleSession.php`
- [x] Registered in bootstrap/app.php (already had import/register, middleware file was missing)
- [x] Wrote 3 feature tests in `tests/Feature/IdleSessionTest.php`
- [x] All 3 tests PASS: admin idle dropped / customer unaffected / absolute 12h cap

## Phase 2 Items (priority order)

### 2.4 Search suggestions Cache::remember(60s) — IN PROGRESS
- [ ] Add Cache::remember to ProductController suggestions
- [ ] Write test proving 2nd identical request makes no DB query
- [ ] Verify test passes

### 2.5 CSP_STRICT mode — NOT STARTED
- [ ] Add CSP_STRICT config (default false) to SecurityHeaders
- [ ] When CSP_STRICT=true: nonce-based script-src, drop unsafe-inline
- [ ] When CSP_STRICT=false: current behavior (unsafe-inline allowed)
- [ ] Smoke test ~10 key routes with CSP_STRICT=true asserting all 200
- [ ] Verify existing tests still pass with CSP_STRICT=false

### D1 docker/start.sh RUN_MIGRATIONS guard — NOT STARTED
- [ ] Add RUN_MIGRATIONS environment variable guard
- [ ] Only run migrations when RUN_MIGRATIONS is not explicitly false

### D2 config/logging.php production daily default — NOT STARTED
- [ ] Add production-specific daily channel config when APP_ENV=production
- [ ] Override LOG_DAILY_DAYS to a production-appropriate value

### D3 SECURITY.md "Backups" section — NOT STARTED
- [ ] Add dedicated "Backups" section to SECURITY.md

### D4 README security status / Laravel 13 deadline — NOT STARTED
- [ ] Add security status section to README
- [ ] Add Laravel 13 deadline (2027-02-24) reference

## Gates (Phase 3)
- [ ] php artisan test — all green
- [ ] composer audit — clean
- [ ] pint --test — passes
- [ ] schedule:list — runs without error
- [ ] Push + PR with green CI