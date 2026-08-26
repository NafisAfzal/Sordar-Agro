/* Phase 9H performance probe — lab measurements only (local server).
 * Collects navigation/paint metrics + network resource audit per page.
 * TEMPORARY diagnostic script — removed in Step 10 cleanup.
 */
const { chromium } = require('@playwright/test');

const BASE = 'http://localhost:8000';
const VIEWPORTS = {
    desktop: { width: 1440, height: 900 },
    mobile: { width: 412, height: 915 },
    tablet: { width: 810, height: 1024 },
};
const PAGES = ['/', '/products', '/products/neon-tetra', '/products/java-fern', '/care-guides', '/login'];

async function measure(page, path) {
    const resources = [];
    const failed = [];
    const pageErr = page.context();
    await page.addInitScript(() => {
        window.__cls = 0;
        window.__lcp = 0;
        new PerformanceObserver(l => {
            for (const e of l.entries) if (!e.hadRecentInput) window.__cls += e.value;
        }).observe({ type: 'layout-shift', buffered: true });
        new PerformanceObserver(l => {
            for (const e of l.entries) window.__lcp = e.startTime;
        }).observe({ type: 'largest-contentful-paint', buffered: true });
    });
    page.on('response', r => {
        const req = r.request();
        if (req.resourceType() === 'document') return; // handled via nav timing
        resources.push({
            type: req.resourceType(),
            url: r.url().replace(BASE, ''),
            status: r.status(),
            bytes: r.headers()['content-length'] ? Number(r.headers()['content-length']) : null,
        });
    });
    page.on('requestfailed', r => failed.push(r.url().replace(BASE, '') + ' :: ' + (r.failure()?.errorText ?? '')));

    const nav = await page.goto(BASE + path, { waitUntil: 'load' });
    await page.waitForTimeout(700); // allow observers to settle
    const m = await page.evaluate(() => {
        const nav = performance.getEntriesByType('navigation')[0];
        const paint = performance.getEntriesByType('paint');
        const fcp = paint.find(p => p.name === 'first-contentful-paint');
        const res = performance.getEntriesByType('resource');
        let transfer = 0, decoded = 0;
        const byType = {};
        for (const r of res) {
            transfer += r.transferSize || 0;
            decoded += r.decodedBodySize || 0;
            const t = r.initiatorType;
            byType[t] = byType[t] || { count: 0, transfer: 0 };
            byType[t].count++;
            byType[t].transfer += r.transferSize || 0;
        }
        const blocking = res.filter(r => r.renderBlockingStatus === 'blocking').map(r => r.name.split('/').pop());
        return {
            ttfb: Math.round(nav.responseStart),
            htmlTransfer: nav.transferSize,
            domContentLoaded: Math.round(nav.domContentLoadedEventEnd),
            loadEvent: Math.round(nav.loadEventEnd),
            fcp: fcp ? Math.round(fcp.startTime) : null,
            lcp: Math.round(window.__lcp),
            cls: Number(window.__cls.toFixed(4)),
            resourceCount: res.length,
            transferTotal: transfer,
            decodedTotal: decoded,
            byType,
            renderBlocking: blocking,
        };
    });
    const status = nav.status();
    return { path, status, ...m, failed, responses: resources };
}

(async () => {
    const browser = await chromium.launch();
    const only = process.argv[2] ?? 'all';
    for (const [name, viewport] of Object.entries(VIEWPORTS)) {
        if (only !== 'all' && only !== name) continue;
        const ctx = await browser.newContext({ viewport });
        const page = await ctx.newPage();
        for (const path of PAGES) {
            const r = await measure(page, path);
            console.log(`\n===== [${name}] ${r.path} (HTTP ${r.status}) =====`);
            console.log(`ttfb=${r.ttfb}ms dcl=${r.domContentLoaded}ms load=${r.loadEvent}ms FCP=${r.fcp}ms LCP=${r.lcp}ms CLS=${r.cls}`);
            console.log(`resources=${r.resourceCount} transfer=${(r.transferTotal / 1024).toFixed(0)}KB decoded=${(r.decodedTotal / 1024).toFixed(0)}KB html=${(r.htmlTransfer / 1024).toFixed(1)}KB`);
            console.log('byType=' + JSON.stringify(r.byType));
            if (r.renderBlocking.length) console.log('renderBlocking=' + JSON.stringify(r.renderBlocking));
            if (r.failed.length) console.log('FAILED=' + JSON.stringify(r.failed));
        }
        await ctx.close();
    }
    await browser.close();
})();
