import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { loginAsCustomer, clearCartRows } from './helpers';

async function scan(page: import('@playwright/test').Page, path: string) {
    await page.goto(path);
    await page.waitForLoadState('domcontentloaded');
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa'])
        .analyze();
    return results.violations;
}

const SERIOUS = ['critical', 'serious'];

test.describe('Automated accessibility (axe, WCAG 2.0/2.1 A+AA)', () => {
    for (const path of ['/', '/products', '/products/neon-tetra', '/care-guides', '/community', '/login', '/register']) {
        test(`no critical/serious violations on ${path} (desktop)`, async ({ page }) => {
            const violations = await scan(page, path);
            const bad = violations.filter(v => SERIOUS.includes(v.impact ?? ''));
            if (bad.length) {
                console.log(path, JSON.stringify(bad.map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.slice(0, 3).map(n => n.target) })), null, 2));
            }
            expect(bad).toEqual([]);
        });
    }

    test('cart page (authenticated) has no critical/serious violations', async ({ page }) => {
        await loginAsCustomer(page);
        await clearCartRows(page);
        const violations = await scan(page, '/cart');
        const bad = violations.filter(v => SERIOUS.includes(v.impact ?? ''));
        if (bad.length) {
            console.log('/cart', JSON.stringify(bad.map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.slice(0, 3).map(n => n.target) })), null, 2));
        }
        expect(bad).toEqual([]);
    });

    test('mobile homepage and product page have no critical/serious violations', async ({ page }, testInfo) => {
        test.skip(!testInfo.project.name.includes('mobile'), 'mobile project only');
        for (const path of ['/', '/products/neon-tetra']) {
            const violations = await scan(page, path);
            const bad = violations.filter(v => SERIOUS.includes(v.impact ?? ''));
            if (bad.length) {
                console.log(path, JSON.stringify(bad.map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.slice(0, 3).map(n => n.target) })), null, 2));
            }
            expect(bad).toEqual([]);
        }
    });
});

test.describe('Keyboard accessibility behaviour', () => {
    test('gallery thumbnails are keyboard operable (Enter switches image)', async ({ page }) => {
        await page.goto('/products/neon-tetra');
        const thumbs = page.locator('.product-gallery-thumb');
        if ((await thumbs.count()) < 2) {
            test.skip(true, 'single-image gallery');
            return;
        }
        const mainBefore = await page.locator('#galleryMain').getAttribute('src');
        await thumbs.nth(1).focus();
        await page.keyboard.press('Enter');
        const mainAfter = await page.locator('#galleryMain').getAttribute('src');
        expect(mainAfter).not.toBe(mainBefore);
        await expect(thumbs.nth(1)).toHaveAttribute('aria-selected', 'true');
    });

    test('quantity stepper buttons expose accessible names', async ({ page }) => {
        await loginAsCustomer(page);
        await page.goto('/products/neon-tetra');
        await expect(page.locator('#qtyMinus')).toHaveAttribute('aria-label');
        await expect(page.locator('#qtyPlus')).toHaveAttribute('aria-label');
        await expect(page.locator('#qtyInput')).toHaveAttribute('aria-label');
    });

    test('login form labels are associated with inputs', async ({ page }) => {
        await page.goto('/login');
        const ok = await page.evaluate(() => {
            const inputs = Array.from(document.querySelectorAll('input[name="email"], input[name="password"]'));
            return inputs.length === 2 && inputs.every(i =>
                Boolean(document.querySelector(`label[for="${i.id}"]`))
            );
        });
        expect(ok).toBeTruthy();
    });

    test('focus is visible on primary CTA (:focus-visible styles present)', async ({ page }) => {
        await page.goto('/');
        const css = await page.evaluate(() => {
            let found = '';
            for (const sheet of Array.from(document.styleSheets)) {
                try {
                    for (const rule of Array.from(sheet.cssRules ?? [])) {
                        if (rule.cssText && rule.cssText.includes(':focus-visible')) found += rule.cssText + ' ';
                    }
                } catch { /* cross-origin CDN sheets */ }
            }
            return found;
        });
        expect(css.length).toBeGreaterThan(0);
    });

    test('prefers-reduced-motion media query exists in stylesheet', async ({ page }) => {
        await page.goto('/');
        const has = await page.evaluate(() =>
            Array.from(document.styleSheets).some(sheet => {
                try {
                    return Array.from(sheet.cssRules ?? []).some(r => r instanceof CSSMediaRule && r.conditionText.includes('prefers-reduced-motion'));
                } catch { return false; }
            })
        );
        expect(has).toBeTruthy();
    });
});
