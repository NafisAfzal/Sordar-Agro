import { test, expect } from '@playwright/test';

test.describe('Mobile navigation contracts', () => {
    test.beforeEach(async ({ page, isMobile }) => {
        test.skip(!isMobile, 'mobile-only behaviour');
        await page.goto('/');
    });

    test('drawer opens with correct ARIA state and closes via Escape with focus restoration', async ({ page }) => {
        const trigger = page.locator('#mobileMenuTrigger');
        const drawer = page.locator('#mobileDrawer');

        await expect(drawer).not.toHaveClass(/active/);
        expect(await trigger.getAttribute('aria-expanded')).toBe('false');
        expect(await drawer.evaluate(el => el.inert)).toBe(true);

        await trigger.click();
        await expect(drawer).toHaveClass(/active/);
        expect(await trigger.getAttribute('aria-expanded')).toBe('true');
        expect(await drawer.getAttribute('aria-hidden')).toBe('false');
        expect(await drawer.evaluate(el => el.inert)).toBe(false);

        // Drawer content is keyboard reachable while open.
        await expect(page.locator('#mobileDrawerClose')).toBeVisible();

        await page.keyboard.press('Escape');
        await expect(drawer).not.toHaveClass(/active/);
        expect(await trigger.getAttribute('aria-expanded')).toBe('false');
        expect(await drawer.getAttribute('aria-hidden')).toBe('true');
        expect(await drawer.evaluate(el => el.inert)).toBe(true);
        // Focus is restored to the trigger.
        await expect(trigger).toBeFocused();
    });

    test('drawer closes via close button and overlay click', async ({ page }) => {
        const drawer = page.locator('#mobileDrawer');
        await page.locator('#mobileMenuTrigger').click();
        await expect(drawer).toHaveClass(/active/);
        await page.locator('#mobileDrawerClose').click();
        await expect(drawer).not.toHaveClass(/active/);

        await page.locator('#mobileMenuTrigger').click();
        await expect(drawer).toHaveClass(/active/);
        // The panel occupies the left edge — click the overlay well clear of it.
        const vw = await page.evaluate(() => window.innerWidth);
        await page.mouse.click(vw - 30, 400);
        await expect(drawer).not.toHaveClass(/active/);
    });

    test('hidden drawer links are not keyboard reachable (inert enforced)', async ({ page }) => {
        const drawer = page.locator('#mobileDrawer');
        await page.keyboard.press('Tab'); // ensure focus starts near top
        const focusInsideClosedDrawer = await page.evaluate(() =>
            document.getElementById('mobileDrawer')!.contains(document.activeElement)
        );
        expect(focusInsideClosedDrawer).toBeFalsy();
        void drawer;
    });

    test('drawer navigates to categories', async ({ page }) => {
        await page.locator('#mobileMenuTrigger').click();
        await page.locator('#mobileDrawer a.mobile-nav-link', { hasText: 'Fish' }).click();
        await page.waitForLoadState('domcontentloaded');
        await expect(page).toHaveURL(/category=fish/);
    });
});

test.describe('Mobile filter drawer', () => {
    test('filter drawer opens, closes on Escape, restores focus', async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'mobile', 'mobile-only');
        await page.goto('/products');
        const trigger = page.locator('#mobileFilterTrigger');
        if (!(await trigger.count())) {
            test.skip(true, 'no mobile filter trigger');
            return;
        }
        const drawer = page.locator('#mobileFilterDrawer');
        expect(await drawer.evaluate(el => el.inert)).toBe(true);

        await trigger.click();
        await expect(drawer).toHaveClass(/open|active/);
        expect(await trigger.getAttribute('aria-expanded')).toBe('true');
        expect(await drawer.evaluate(el => el.inert)).toBe(false);

        await page.locator('#mobileFilterClose').click();
        await expect(trigger).toBeFocused();
        expect(await drawer.evaluate(el => el.inert)).toBe(true);
    });
});

test.describe('Responsive layout integrity across required viewports', () => {
    const sizes: Array<[number, number]> = [
        [360, 800], [375, 812], [390, 844], [412, 915],
        [768, 1024], [1024, 1366], [1440, 900],
    ];
    const pages = ['/', '/products', '/products/neon-tetra', '/products/java-fern', '/care-guides'];

    for (const [width, height] of sizes) {
        for (const path of pages) {
            test(`no horizontal overflow at ${width}x${height} on ${path}`, async ({ page }, testInfo) => {
                test.skip(testInfo.project.name !== 'desktop', 'matrix runs once; sizes cover all breakpoints');
                await page.setViewportSize({ width, height });
                await page.goto(path);
                await page.waitForLoadState('domcontentloaded');
                const overflow = await page.evaluate(() =>
                    document.documentElement.scrollWidth - document.documentElement.clientWidth
                );
                expect(overflow, `horizontal overflow of ${overflow}px at ${width}x${height}`).toBeLessThanOrEqual(1);
            });
        }
    }
});
