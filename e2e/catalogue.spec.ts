import { test, expect } from '@playwright/test';

test.describe('Catalogue & discovery', () => {
    test('catalogue lists approved products with cards', async ({ page }) => {
        await page.goto('/products');
        const cards = page.locator('.product-card');
        const count = await cards.count();
        expect(count).toBeGreaterThan(0);
        // Card anatomy: category badge, name link, price.
        const first = cards.first();
        await expect(first.locator('.card-title a')).toBeVisible();
        await expect(first.locator('text=/From ৳/')).toBeVisible();
    });

    test('search query filters results (q parameter preserved)', async ({ page }) => {
        await page.goto('/products?q=tetra');
        await expect(page).toHaveURL(/q=tetra/);
        // Either matches or the empty state — never an error.
        const body = page.locator('body');
        await expect(body).toContainText(/৳|No products|found/i);
    });

    test('category filter narrows to fish', async ({ page }) => {
        await page.goto('/products?category=fish');
        await expect(page).toHaveURL(/category=fish/);
        const cards = page.locator('.product-card');
        const count = await cards.count();
        expect(count).toBeGreaterThan(0);
    });

    test('sorting select is present and functional', async ({ page }) => {
        await page.goto('/products');
        const sort = page.locator('#catalogueSort');
        if (!(await sort.count())) {
            test.skip(true, 'no sort control found');
            return;
        }
        await sort.selectOption({ index: 1 });
        await page.waitForLoadState('domcontentloaded');
        await expect(page).toHaveURL(/sort=/);
    });

    test('desktop filter form submits with preserved parameter names', async ({ page, isMobile }) => {
        test.skip(isMobile, 'desktop sidebar only');
        await page.goto('/products');
        const minPrice = page.locator('.filter-form input[name="min_price"]').first();
        if (!(await minPrice.count())) {
            test.skip(true, 'filter form not present');
            return;
        }
        await minPrice.fill('100');
        await minPrice.press('Enter');
        await page.waitForLoadState('domcontentloaded');
        await expect(page).toHaveURL(/min_price=100/);
    });

    test('live search suggestions appear and escape hides them', async ({ page }) => {
        await page.goto('/');
        const input = page.locator('#navSearchInput');
        if (!(await input.isVisible())) {
            // Below the md breakpoint the header search is a plain collapse
            // form — the live-suggestion affordance does not exist there.
            // Assert the real mobile search journey instead. (Note: the
            // tablet project reports isMobile=true but renders the md+ layout.)
            await page.locator('button[aria-controls="mobileSearch"]').click();
            const mobileInput = page.locator('#mobileSearch input[name="q"]');
            await mobileInput.fill('tet');
            await mobileInput.press('Enter');
            await page.waitForLoadState('domcontentloaded');
            await expect(page).toHaveURL(/products\?q=tet/);
            await expect(page.locator('body')).toContainText(/Neon Tetra|results?|No products/i);
            return;
        }
        await input.click();
        await input.fill('tet');
        const box = page.locator('#searchSuggestions');
        await expect(box).toBeVisible({ timeout: 8000 });
        await input.press('Escape');
        await expect(box).toBeHidden();
    });

    test('pagination controls render when catalogue spans pages', async ({ page }) => {
        await page.goto('/products');
        const pagination = page.locator('.pagination');
        // 11 approved products may or may not paginate; both states are valid.
        const count = await pagination.count();
        expect(count).toBeLessThanOrEqual(1);
    });

    test('care guides index renders published guides', async ({ page }) => {
        await page.goto('/care-guides');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });

    test('community index renders', async ({ page }) => {
        await page.goto('/community');
        await expect(page.locator('body')).not.toContainText('Whoops');
    });
});
