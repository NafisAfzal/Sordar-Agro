import { test, expect } from '@playwright/test';
import { loginAs, loginAsCustomer, clearCartRows, CUSTOMER } from './helpers';

/**
 * Visual regression baselines (brief Â§33). Run once to establish baselines;
 * future frontend changes re-run with --update-snapshots and review diffs.
 */
const GUEST_SHOTS: Array<[string, string]> = [
    ['home', '/'],
    ['catalogue', '/products'],
    ['product-fish', '/products/neon-tetra'],
    ['product-nonfish', '/products/java-fern'],
    ['care-guides', '/care-guides'],
    ['login', '/login'],
    ['registration', '/register'],
];

test.describe('Visual regression baselines', () => {
    for (const [name, path] of GUEST_SHOTS) {
        test(`baseline: ${name}`, async ({ page }) => {
            await page.goto(path);
            await page.waitForLoadState('networkidle');
            // Stock badges show live inventory, which paid E2E orders legally
            // decrement during a run â€” mask them on product pages.
            // Freeze the stock-badge box so the mask rectangle cannot shift
            // by a pixel when the live stock digit count changes (e.g.
            // "8 units" vs "40 units" after a paid E2E order decremented it).
            if (name.startsWith('product-')) {
                await page.addStyleTag({ content: '#stockLabel,#mobileStockLabel{display:inline-block !important;min-width:8rem !important;text-align:left !important}' });
            }
            const mask = name.startsWith('product-')
                ? [page.locator('#stockLabel'), page.locator('#mobileStockLabel')]
                : [];
            await expect(page).toHaveScreenshot(`${name}.png`, { fullPage: true, mask });
        });
    }

    test('baseline: cart (authenticated)', async ({ page }) => {
        await loginAsCustomer(page);
        // Build the SAME deterministic state the baseline was captured with
        // (Java Fern standard x1). A bare /cart on a clean database renders
        // the empty state, which is not what the baseline represents; other
        // suites also create/clear cart rows around a run.
        await page.goto('/products/java-fern');
        const addPost = page.waitForResponse(r => r.url().includes('/cart/') && r.request().method() === 'POST');
        await page.click('#addForm button.btn-sa');
        await addPost;
        // Consume the redirect render so the success flash cannot leak onto
        // the cart page when the goto outruns it (same as the checkout test).
        await page.waitForLoadState('domcontentloaded');
        await page.goto('/cart');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveScreenshot('cart.png', { fullPage: true });
        await clearCartRows(page);
    });

    test('baseline: checkout (authenticated, item in cart)', async ({ page }) => {
        await loginAsCustomer(page);
        await page.goto('/products/java-fern');
        // Await the add POST: a bare click race can abort the form submit
        // before the cart row commits, leaving the summary empty.
        const addPost = page.waitForResponse(r => r.url().includes('/cart/') && r.request().method() === 'POST');
        await page.click('#addForm button.btn-sa');
        await addPost;
        // Consume the redirect render so the success flash cannot leak onto
        // the checkout page when the goto outruns it.
        await page.waitForLoadState('domcontentloaded');
        await page.goto('/checkout');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveScreenshot('checkout.png', { fullPage: true });
        // Leave no cart row behind (cleanup script also runs suite-side).
        await clearCartRows(page);
    });

    test('baseline: seller dashboard', async ({ page }) => {
        await loginAs(page, 'seller@example.com', CUSTOMER.password);
        await page.goto('/seller/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveScreenshot('seller-dashboard.png', { fullPage: true });
    });

    test('baseline: admin dashboard', async ({ page }) => {
        await loginAs(page, 'admin@example.com', CUSTOMER.password);
        await page.goto('/admin');
        // Random order numbers (SA-XXXXXXXX) must not influence layout:
        // fixed columns keep widths content-independent, and nowrap stops
        // varying wrap counts from changing row heights on narrow screens.
        // The masks hide every order-derived region: the random identifiers,
        // the Orders/Revenue/Marketplace-share stat values, the share
        // breakdown body and the Recent-orders rows (totals/badges vary
        // with how many E2E orders earlier suites in a run have created).
        await page.addStyleTag({ content: '.card table:not(.table-sm) { table-layout: fixed; } .card table:not(.table-sm) td:first-child { white-space: nowrap; overflow: hidden; }' });
        await page.waitForLoadState('networkidle');
        const statValue = (label: string) =>
            page.locator('.row.g-3.mb-5 > div', { hasText: new RegExp(`^${label}$`) }).locator('.fs-5.fw-bold');
        await expect(page).toHaveScreenshot('admin-dashboard.png', {
            fullPage: true,
            mask: [
                statValue('Orders'),
                statValue('Revenue \\(paid\\)'),
                statValue('Marketplace share \\(paid\\)'),
                page.locator('.card:has-text("Marketplace share by seller") tbody'),
                page.locator('.card:has-text("Recent orders") tbody'),
            ],
        });
    });
});
