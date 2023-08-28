const playwright = require('playwright');
const { config } = require('../fixtures/config');

(async () => {
    const browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(config.redcapUrl);

    await page.locator('input[name="dl-option"][value="upload"]').waitFor({ state: 'visible' });
    await page.locator('input[name="dl-option"][value="upload"]').check();

    await page.locator('input#installer-upload').waitFor({ state: 'visible' });
    await page.locator('input#installer-upload').setInputFiles('redcap13.1.27.zip');

    await page.locator('input[name="init-table"]').check();

    await page.locator('input[name="init-table-email"]').waitFor({ state: 'visible' });
    await page.locator('input[name="init-table-email"]').fill('andrew.poppe@yale.edu');

    await page.locator('button.initiate-installation').click();

    await page.locator('div', { hasText: 'Building your REDCap Server' }).waitFor({ state: 'visible' });

    await page.locator('div.alert-success', { hasText: 'Created users: admin alice bob carol dan' }).waitFor({ state: 'visible', timeout: 300000 });

})();