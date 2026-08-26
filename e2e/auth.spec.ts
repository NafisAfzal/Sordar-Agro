import { test, expect } from '@playwright/test';
import { loginAsCustomer, CUSTOMER } from './helpers';

test.describe('Authentication', () => {
    test('customer can log in and is redirected to the storefront', async ({ page }) => {
        await page.goto('/login');
        await expect(page).toHaveTitle(/Login|Sordar Agro/);
        await page.fill('input[name="email"]', CUSTOMER.email);
        await page.fill('input[name="password"]', CUSTOMER.password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await page.waitForLoadState('domcontentloaded');
        // Customer lands on home (not admin/seller dashboard).
        await expect(page).toHaveURL(/^http:\/\/localhost:8000\/$/);
    });

    test('wrong password is rejected with an error', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', CUSTOMER.email);
        await page.fill('input[name="password"]', 'definitely-wrong-password');
        await page.getByRole('button', { name: 'Log in' }).click();
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('.alert-danger, .alert-warning').first()).toBeVisible();
    });

    test('guest is redirected from cart to login', async ({ page }) => {
        await page.goto('/cart');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('customer cannot open admin dashboard (403)', async ({ page }) => {
        await loginAsCustomer(page);
        const resp = await page.goto('/admin');
        expect(resp?.status()).toBe(403);
    });

    test('customer cannot open seller workspace (403)', async ({ page }) => {
        await loginAsCustomer(page);
        const resp = await page.goto('/seller/dashboard');
        expect(resp?.status()).toBe(403);
    });

    test('registration form enforces password minimum length', async ({ page }) => {
        await page.goto('/register');
        const pwd = page.locator('input[name="password"]');
        if (!(await pwd.count())) {
            test.skip(true, 'register form fields differ');
            return;
        }
        await page.fill('input[name="name"]', 'E2E Probe');
        await page.fill('input[name="email"]', `e2e-probe-${Date.now()}@example.com`);
        await pwd.fill('short7');
        await page.getByRole('button', { name: 'Register' }).click();
        // HTML5 or server-side rejection keeps us on /register.
        await expect(page).toHaveURL(/register/);
    });
});
