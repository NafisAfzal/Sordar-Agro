import { Page, expect } from '@playwright/test';

export const CUSTOMER = { email: 'customer@example.com', password: 'password' };

/**
 * Submit the login form and await the POST response itself: a following
 * goto() can otherwise abort the in-flight login, leaving the session
 * unauthenticated while the test proceeds as if it were logged in.
 */
export async function loginAs(page: Page, email: string, password: string): Promise<void> {
    await page.goto('/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    const loginPost = page.waitForResponse(r => r.url().includes('/login') && r.request().method() === 'POST');
    await page.getByRole('button', { name: 'Log in' }).click();
    await loginPost;
}

export async function loginAsCustomer(page: Page): Promise<void> {
    await loginAs(page, CUSTOMER.email, CUSTOMER.password);
    await page.waitForLoadState('domcontentloaded');
    // Header shows the account dropdown once authenticated.
    await expect(page.locator('.header-action-btn.dropdown-toggle')).toBeVisible({ timeout: 10000 });
}

export async function logout(page: Page): Promise<void> {
    await page.goto('/'); // ensure storefront layout
    const trigger = page.locator('.header-action-btn.dropdown-toggle');
    if (await trigger.count()) {
        await trigger.first().click();
        await page.click('.dropdown-menu button:has-text("Log out")');
        await page.waitForLoadState('domcontentloaded');
    }
}

const dialogAcceptorPages = new WeakSet<Page>();

/** Installs (once per page) an accept-all dialog handler. Idempotent. */
export function acceptDialogs(page: Page): void {
    if (dialogAcceptorPages.has(page)) return;
    dialogAcceptorPages.add(page);
    page.on('dialog', d => {
        d.accept().catch(() => { /* already handled by another listener */ });
    });
}

/** Removes every cart row through the real UI (accepting confirm dialogs). */
export async function clearCartRows(page: Page): Promise<void> {
    await page.goto('/cart');
    // Must be installed even when the cart is already empty: later remove
    // clicks in the same test rely on this acceptor (confirm() default is
    // dismissal, which silently cancels the remove).
    acceptDialogs(page);
    if (await page.locator('.cart-empty').count()) return;
    let guard = 0;
    while (!(await page.locator('.cart-empty').count()) && guard < 20) {
        const btns = page.locator('.cart-remove-btn');
        const before = await btns.count();
        if (!before) break;
        // dispatchEvent avoids Playwright's post-navigation click retry,
        // which can hang when the form submit navigates mid-verification.
        // The confirm() gate and server-side removal are still exercised.
        await btns.first().dispatchEvent('click');
        await expect(btns).toHaveCount(before - 1, { timeout: 5000 });
        guard++;
    }
    await expect(page.locator('.cart-empty')).toBeVisible({ timeout: 5000 });
}
