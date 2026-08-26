const { webkit } = require('@playwright/test');

(async () => {
    const browser = await webkit.launch();
    const page = await browser.newPage({ viewport: { width: 768, height: 1024 } });
    await page.goto('http://localhost:8000/');
    await page.locator('#mobileMenuTrigger').click();
    await page.waitForTimeout(500);

    const boxes = await page.evaluate(() => {
        const d = document.getElementById('mobileDrawer').getBoundingClientRect();
        const o = document.getElementById('mobileDrawerOverlay').getBoundingClientRect();
        const el = document.elementFromPoint(5, 5);
        return {
            drawer: { x: d.x, y: d.y, w: d.width, h: d.height },
            overlay: { x: o.x, y: o.y, w: o.width, h: o.height },
            topElementAt5x5: el ? el.tagName + '#' + (el.id || el.className) : 'none',
        };
    });
    console.log(JSON.stringify(boxes, null, 2));

    // Click overlay at a point guaranteed OUTSIDE the drawer panel (right edge).
    const vw = 768;
    await page.mouse.click(vw - 30, 400);
    await page.waitForTimeout(600);
    const cls = await page.evaluate(() => document.getElementById('mobileDrawer').className);
    console.log('after right-side overlay click, drawer class:', cls);

    // Also probe the original 5,5 point:
    await page.locator('#mobileMenuTrigger').click();
    await page.waitForTimeout(500);
    await page.mouse.click(5, 5);
    await page.waitForTimeout(600);
    console.log('after 5,5 click, drawer class:', await page.evaluate(() => document.getElementById('mobileDrawer').className));

    await browser.close();
})();
