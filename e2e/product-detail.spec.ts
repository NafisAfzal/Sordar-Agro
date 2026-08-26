import { test, expect } from '@playwright/test';
import { loginAsCustomer } from './helpers';

test.describe('Product detail — fish (neon-tetra)', () => {
    test.beforeEach(async ({ page }) => {
        // Purchase panel (#addForm, qty stepper) renders for authenticated shoppers.
        await loginAsCustomer(page);
        await page.goto('/products/neon-tetra');
    });

    test('shows fish-specific pair wording and meta', async ({ page }) => {
        await expect(page.locator('.fish-pair-note')).toContainText(/pair/i);
        await expect(page.locator('h1.product-name')).toHaveText('Neon Tetra');
    });

    test('renders three size pills with Small active by default', async ({ page }) => {
        const pills = page.locator('#sizePills .size-pill');
        await expect(pills).toHaveCount(3);
        await expect(pills.nth(0)).toHaveClass(/active/);
        await expect(pills.filter({ hasText: 'Small' })).toBeVisible();
        await expect(pills.filter({ hasText: 'Medium' })).toBeVisible();
        await expect(pills.filter({ hasText: 'Large' })).toBeVisible();
    });

    test('default price and stock reflect the first in-stock variant', async ({ page }) => {
        await expect(page.locator('#priceLabel')).toHaveText('180.00');
        await expect(page.locator('#stockLabel')).toContainText('25 pairs in stock');
    });

    test('switching variant updates price, stock, description and form action', async ({ page }) => {
        const medium = page.locator('#sizePills .size-pill', { hasText: 'Medium' });
        await medium.click();

        await expect(medium).toHaveClass(/active/);
        await expect(page.locator('#priceLabel')).toHaveText('240.00');
        await expect(page.locator('#stockLabel')).toContainText('18 pairs in stock');
        await expect(page.locator('#sizeDesc')).toHaveText('2.5–3 cm sub-adults');

        const action = await page.locator('#addForm').getAttribute('action');
        expect(action).toMatch(/\/cart\/\d+$/);
        // The add-form action must point at the Medium variant id (2), not the default.
        expect(action?.endsWith('/2')).toBeTruthy();
    });

    test('quantity stepper respects the selected variant stock cap', async ({ page }) => {
        const large = page.locator('#sizePills .size-pill', { hasText: 'Large' });
        await large.click();

        // Center-scroll explicitly: engine-dependent scrollIntoView can park
        // controls beneath the sticky header on narrower viewports.
        await page.evaluate(() => document.getElementById('qtyPlus')?.scrollIntoView({ block: 'center' }));

        const qty = page.locator('#qtyInput');
        const plus = page.locator('#qtyPlus');
        for (let i = 0; i < 15; i++) {
            // Re-center before every click: auto-scroll can park the button
            // under the sticky header or the mobile purchase bar, and the
            // resulting hit-target retry loop oscillates.
            await plus.evaluate(el => el.scrollIntoView({ block: 'center', behavior: 'instant' }));
            await plus.click();
        }
        // Large stock = 10; stepper must stop there.
        expect(await qty.inputValue()).toBe('10');
        expect(await qty.getAttribute('max')).toBe('10');

        await page.locator('#qtyMinus').click();
        expect(await qty.inputValue()).toBe('9');
    });

    test('care tab exposes tank size, temperament and sold-as-pairs info', async ({ page }) => {
        await page.click('#tab-care');
        const panel = page.locator('#panel-care');
        await expect(panel).toContainText('Minimum Tank Size');
        await expect(panel).toContainText('Temperament');
        await expect(panel).toContainText('Pairs (2 fish per unit)');
    });

    test('mobile sticky purchase bar mirrors selected variant', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'sticky bar is a mobile-only affordance');
        await expect(page.locator('#mobilePriceLabel')).toContainText('180.00');
        await page.locator('#sizePills .size-pill', { hasText: 'Large' }).click();
        await expect(page.locator('#mobilePriceLabel')).toContainText('320.00');
        await expect(page.locator('#mobileStockLabel')).toContainText('10 pairs');
    });
});

test.describe('Product detail — non-fish (java-fern)', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsCustomer(page);
        await page.goto('/products/java-fern');
    });

    test('does not render a size selector', async ({ page }) => {
        await expect(page.locator('#sizePills')).toHaveCount(0);
    });

    test('standard variant shows correct price and unit stock wording', async ({ page }) => {
        await expect(page.locator('#priceLabel')).toHaveText('150.00');
        // The business rule under test is the non-fish "units" wording; the
        // exact count is state-dependent (paid E2E orders decrement stock
        // earlier in a full run).
        await expect(page.locator('#stockLabel')).toContainText(/\d+ units in stock/);
    });

    test('no fish pair note is shown', async ({ page }) => {
        await expect(page.locator('.fish-pair-note')).toHaveCount(0);
    });

    test('care tab is absent for plants but overview and shipping exist', async ({ page }) => {
        await expect(page.locator('#tab-care')).toHaveCount(0);
        await expect(page.locator('#panel-overview')).toBeVisible();
        await page.click('#tab-shipping');
        await expect(page.locator('#panel-shipping')).toContainText('bKash');
    });
});
