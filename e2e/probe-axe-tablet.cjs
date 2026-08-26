const { webkit } = require('@playwright/test');
const { AxeBuilder } = require('@axe-core/playwright');

(async () => {
    const browser = await webkit.launch();
    const context = await browser.newContext({ viewport: { width: 768, height: 1024 } });
    const page = await context.newPage();

    for (const path of ['/', '/products', '/products/neon-tetra', '/care-guides', '/community', '/login', '/register']) {
        await page.goto('http://localhost:8000' + path);
        await page.waitForLoadState('domcontentloaded');
        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa'])
            .analyze();
        const bad = results.violations.filter(v => ['critical', 'serious'].includes(v.impact ?? ''));
        console.log(`\n===== ${path} : ${bad.length} serious/critical =====`);
        for (const v of bad) {
            console.log(`  [${v.impact}] ${v.id}`);
            for (const n of v.nodes.slice(0, 5)) {
                console.log(`    target: ${JSON.stringify(n.target)}`);
                const data = n.any[0]?.data;
                if (data?.contrastRatio) {
                    console.log(`    ratio: ${data.contrastRatio} fg:${data.fgColor} bg:${data.bgColor} ${data.fontSize}/${data.fontWeight}`);
                }
                console.log(`    html: ${n.html.slice(0, 160)}`);
            }
        }
    }
    await browser.close();
})();
