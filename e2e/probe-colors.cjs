const { chromium } = require('@playwright/test');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

    await page.goto('http://localhost:8000/products/neon-tetra');
    await page.waitForLoadState('networkidle');

    const probe = await page.evaluate(() => {
        function info(sel, all = false) {
            const el = all ? document.querySelector(sel) : document.querySelector(sel);
            if (!el) return sel + ' => NOT FOUND';
            const cs = getComputedStyle(el);
            // walk up for effective background
            let bgEl = el, bg = 'transparent';
            while (bgEl && bgEl !== document.documentElement) {
                const b = getComputedStyle(bgEl).backgroundColor;
                if (b && b !== 'rgba(0, 0, 0, 0)' && b !== 'transparent') { bg = b; break; }
                bgEl = bgEl.parentElement;
            }
            return `${sel} => color:${cs.color} bg:${bg} fs:${cs.fontSize} fw:${cs.fontWeight}`;
        }
        return [
            info('.product-detail .breadcrumb-item:nth-child(1) a'),
            info('.badge-new', true),
            info('footer .text-secondary.small.mb-0'),
            info('footer p.text-secondary.mb-3'),
        ].join('\n');
    });
    console.log('--- PRODUCT PAGE ---\n' + probe);

    await page.goto('http://localhost:8000/');
    await page.waitForLoadState('networkidle');
    const probe2 = await page.evaluate(() => {
        const badge = document.querySelector('.badge-new');
        if (!badge) return '.badge-new not present on home';
        const cs = getComputedStyle(badge);
        return `.badge-new color:${cs.color} bg:${cs.backgroundColor}`;
    });
    console.log('--- HOME ---\n' + probe2);

    await browser.close();
})();
