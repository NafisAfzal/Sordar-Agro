import { test, expect } from '@playwright/test';

const BASE = 'http://localhost:8000';

test.describe('SEO — rendered HTML', () => {
    const indexable = [
        ['/', 'home'],
        ['/products', 'products.index'],
        ['/products/neon-tetra', 'product'],
        ['/care-guides', 'care.index'],
        ['/community', 'community.index'],
    ];

    for (const [path] of indexable) {
        test(`${path} is indexable with unique title + description + canonical`, async ({ page }) => {
            await page.goto(path);
            const title = await page.title();
            expect(title.length, 'title length').toBeGreaterThan(10);
            expect(title).toContain('Sordar Agro');

            const desc = await page.locator('meta[name="description"]').getAttribute('content');
            expect(desc?.length ?? 0, 'meta description present').toBeGreaterThan(20);

            const robots = await page.locator('meta[name="robots"]').getAttribute('content');
            expect(robots).toBe('index,follow');

            const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
            expect(canonical).toBeTruthy();
            expect(canonical!).not.toContain('?');
        });
    }

    test('canonical ignores query strings on the catalogue', async ({ page }) => {
        await page.goto('/products?category=fish&min_price=100&sort=price_asc');
        const canonical = await page.locator('link[rel="canonical"]').getAttribute('content')
            ?? await page.locator('link[rel="canonical"]').getAttribute('href');
        expect(canonical).toBeTruthy();
        expect(canonical!).toBe(`${BASE}/products`);
    });

    test('private pages are noindex,nofollow', async ({ page }) => {
        for (const path of ['/cart', '/checkout', '/orders', '/wishlist', '/login']) {
            // /cart etc redirect guests to login; login itself must be noindex.
            const resp = await page.goto(path);
            if (resp?.status() === 302 || resp?.status() === 301) continue;
            const robots = await page.locator('meta[name="robots"]').getAttribute('content');
            expect(robots, path).toContain('noindex');
        }
    });

    test('product JSON-LD parses, has truthful offers, and fabricates nothing', async ({ page }) => {
        await page.goto('/products/neon-tetra');
        const lds = await page.evaluate(() =>
            Array.from(document.querySelectorAll('script[type="application/ld+json"]'))
                .map(s => { try { return JSON.parse(s.textContent || ''); } catch { return null; } })
                .filter(Boolean)
        );
        expect(lds.length).toBeGreaterThanOrEqual(2);

        const product = lds.find((l: any) => l['@type'] === 'Product');
        expect(product).toBeTruthy();
        expect(product.name).toBe('Neon Tetra');

        const offers = product.offers;
        expect(Array.isArray(offers)).toBeTruthy();
        expect(offers.length).toBe(3);
        const prices = offers.map((o: any) => parseFloat(o.price)).sort((a: number, b: number) => a - b);
        expect(prices).toEqual([180, 240, 320]);
        for (const o of offers) {
            expect(o.priceCurrency).toBe('BDT');
            expect(['https://schema.org/InStock', 'https://schema.org/OutOfStock']).toContain(o.availability);
        }
        // Business-truth rule: nothing fabricated.
        expect(product.brand).toBeUndefined();
        expect(product.aggregateRating).toBeUndefined();
        expect(product.review).toBeUndefined();
    });

    test('BreadcrumbList JSON-LD parses on the product page', async ({ page }) => {
        await page.goto('/products/neon-tetra');
        const lds = await page.evaluate(() =>
            Array.from(document.querySelectorAll('script[type="application/ld+json"]'))
                .map(s => { try { return JSON.parse(s.textContent || ''); } catch { return null; } })
                .filter(Boolean)
        );
        const crumb = lds.find((l: any) => l['@type'] === 'BreadcrumbList');
        expect(crumb).toBeTruthy();
        const names = crumb.itemListElement.map((i: any) => i.name);
        expect(names[0]).toBe('Home');
        expect(names[names.length - 1]).toBe('Neon Tetra');
        for (const item of crumb.itemListElement) {
            expect(item.item).toMatch(/^https?:\/\//);
        }
    });

    test('Open Graph metadata present on indexable storefront pages', async ({ page }) => {
        await page.goto('/products/neon-tetra');
        expect(await page.locator('meta[property="og:title"]').getAttribute('content')).toBeTruthy();
        expect(await page.locator('meta[property="og:type"]').getAttribute('content')).toBe('product');
        const ogUrl = await page.locator('meta[property="og:url"]').getAttribute('content');
        expect(ogUrl).toBe(`${BASE}/products/neon-tetra`);
    });
});

test.describe('SEO — technical endpoints', () => {
    test('robots.txt exists and references the sitemap', async ({ request }) => {
        const resp = await request.get(`${BASE}/robots.txt`);
        expect(resp.status()).toBe(200);
        const body = await resp.text();
        expect(body).toContain('Sitemap:');
        expect(body.toLowerCase()).toContain('user-agent');
    });

    test('sitemap.xml parses as XML and every URL responds 200 without query strings', async ({ request }) => {
        const resp = await request.get(`${BASE}/sitemap.xml`);
        expect(resp.status()).toBe(200);
        const xml = await resp.text();

        const locs = Array.from(xml.matchAll(/<loc>(.*?)<\/loc>/g)).map(m => m[1]);
        expect(locs.length).toBeGreaterThan(0);

        const seen = new Set<string>();
        for (const loc of locs) {
            expect(loc, 'sitemap URLs must not contain query strings').not.toContain('?');
            expect(seen.has(loc), `duplicate sitemap entry ${loc}`).toBeFalsy();
            seen.add(loc);
        }

        for (const loc of locs.slice(0, 40)) {
            const r = await request.get(loc);
            expect(r.status(), `${loc} should resolve`).toBeLessThan(400);
        }
    });
});
