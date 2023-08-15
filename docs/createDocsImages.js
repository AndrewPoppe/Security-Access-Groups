const playwright = require('playwright');
const sharp = require('sharp');
const creds = require('../creds.json');

const colors = {
    project: '#2F5FD9',
    control_center: '#2FD95F',
    other: '#D92F5F'
}
const borderThickness = 6;
const projectId = 16;
const redcapVersion = 'redcap_v13.1.27';


(async () => {
    const browser = await playwright.chromium.launch();
    const context = await browser.newContext({ viewport: { width: 1865, height: 947 } });
    const page = await context.newPage();

    // Login
    await page.goto(`http://localhost:13740/${redcapVersion}/ExternalModules/manager/control_center.php`);
    await page.locator('#username').fill(creds.admin_username);
    await page.locator('#password').fill(creds.admin_password);

    await page.click('#login_btn');
    await page.waitForURL('**/ExternalModules/manager/control_center.php');


    await page.locator('tr[data-module="security_access_groups"] button.external-modules-configure-button').click();
    await page.locator('select[name="reserved-language-system"]').waitFor('visible');

    const img3 = await page.locator('#external-modules-configure-modal .modal-dialog').screenshot();
    await sharp(img3)
        .extend({
            top: borderThickness,
            bottom: borderThickness,
            left: borderThickness,
            right: borderThickness,
            background: colors.project
        })
        .toFile('screenshot3.png');


    // Go to Control Center
    // await page.getByText('Control Center').click();
    // await page.waitForURL('**/ControlCenter/index.php');

    await browser.close();
})();