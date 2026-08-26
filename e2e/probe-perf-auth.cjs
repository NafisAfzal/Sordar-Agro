/* Phase 9H — authenticated pages measurement (desktop only). TEMPORARY. */
const { chromium } = require('@playwright/test');
const BASE = 'http://localhost:8000';

(async () => {
    const browser = await chromium.launch();
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    await page.goto(BASE + '/login');
    await page.fill('input[name="email"]', 'customer@example.com');
    await page.fill('input[name="password"]', 'password');
    await Promise.all([
        page.waitForResponse(r => r.url().includes('/login') && r.request().method() === 'POST'),
        page.getByRole('button', { name: 'Log in' }).click(),
    ]);
    await page.waitForLoadState('domcontentloaded');

    for (const path of ['/cart', '/checkout', '/orders']) {
        const res = [];
        page.on('response', r => { if (r.request().resourceType() !== 'document') res.push(r.request().resourceType() + ':' + (r.headers()['content-length'] ?? '?')); });
        const t0 = Date.now();
        const nav = await page.goto(BASE + path, { waitUntil: 'load' });
        const m = await page.evaluate(() => {
            const n = performance.getEntriesByType('navigation')[0];
            const paint = performance.getEntriesByType('paint').find(p => p.name === 'first-contentful-paint');
            return { ttfb: Math.round(n.responseStart), dcl: Math.round(n.domContentLoadedEventEnd), load: Math.round(n.loadEventEnd), fcp: paint ? Math.round(paint.startTime) : null };
        });
        console.log(`${path}: HTTP ${nav.status()} ttfb=${m.ttfb} dcl=${m.dcl} load=${m.load} fcp=${m.fcp} subresources=${res.length}`);
        res.length = 0;
    }

    // admin + seller with their accounts
    const admin = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const ap = await admin.newPage();
    for (const [role, email, path] of [['admin', 'admin@example.com', '/admin'], ['seller', 'seller@example.com', '/seller/dashboard']]) {
        await ap.goto(BASE + '/login');
        await ap.fill('input[name="email"]', email);
        await ap.fill('input[name="password"]', 'password');
        await Promise.all([
            ap.waitForResponse(r => r.url().includes('/login') && r.request().method() === 'POST'),
            ap.getByRole('button', { name: 'Log in' }).click(),
        ]);
        await ap.waitForLoadState('domcontentloaded');
        const t0 = Date.now();
        const nav = await ap.goto(BASE + path, { waitUntil: 'load' });
        const m = await ap.evaluate(() => {
            const n = performance.getEntriesByType('navigation')[0];
            return { ttfb: Math.round(n.responseStart), dcl: Math.round(n.domContentLoadedEventEnd), load: Math.round(n.loadEventEnd) };
        });
        console.log(`${path} (${role}): HTTP ${nav.status()} ttfb=${m.ttfb} dcl=${m.dcl} load=${m.load}`);
        await ap.context().clearCookies();
    }
    await browser.close();
})();
