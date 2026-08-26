const { chromium } = require('@playwright/test');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.goto('http://localhost:8000/login');
    await page.fill('input[name="email"]', 'customer@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForLoadState('domcontentloaded');
    console.log('after-login URL:', page.url());

    const authed = await page.evaluate(() =>
        Boolean(document.querySelector('.header-action-btn.dropdown-toggle')));
    console.log('dropdown visible:', authed);

    await page.goto('http://localhost:8000/products/java-fern');
    const html = await page.content();
    console.log('has #addForm:', html.includes('id="addForm"'));
    console.log('has "Log in to buy":', html.includes('Log in to buy'));
    console.log('has admin-note text:', html.includes('Administrators shop'));
    const stock = await page.locator('#stockLabel').textContent().catch(() => 'NOT FOUND');
    console.log('stockLabel:', stock);
    // dump purchase area
    const purchaseHtml = await page.evaluate(() => {
        const el = document.querySelector('.product-purchase');
        return el ? el.outerHTML.slice(0, 1500) : '.product-purchase NOT FOUND';
    });
    console.log('--- purchase panel ---\n' + purchaseHtml);
    await browser.close();
})();
