const playwright = require('playwright');
const sharp = require('sharp');
const fs = require('fs');

const colors = {
    project: '#2F5FD9',
    control_center: '#2FD95F',
    other: '#D92F5F'
}
const borderThickness = 6;
const projectId = 16;
const urlBase = 'http://localhost:13740';
const redcapVersion = 'redcap_v13.1.27';
const screenshotsPath = `screenshots`;

const languages = [
    { selectValue: "", label: "English", code: "EN" },
    { selectValue: "Arabic (عربي)", label: "Arabic", code: "AR" },
    { selectValue: "Bangla (বাংলা)", label: "Bangla", code: "BN" },
    { selectValue: "Chinese (中文)", label: "Chinese", code: "ZH" },
    { selectValue: "French (Français)", label: "French", code: "FR" },
    { selectValue: "German (Deutsch)", label: "German", code: "DE" },
    { selectValue: "Hindi (हिंदी)", label: "Hindi", code: "HI" },
    { selectValue: "Italian (Italiana)", label: "Italian", code: "IT" },
    { selectValue: "Portuguese (Português)", label: "Portuguese", code: "PT" },
    { selectValue: "Spanish (Español)", label: "Spanish", code: "ES" },
    { selectValue: "Ukrainian (українська)", label: "Ukrainian", code: "UK" },
    { selectValue: "Urdu (اردو)", label: "Urdu", code: "UR" },
];

const cc_config_options = {
    top: borderThickness,
    bottom: borderThickness,
    left: borderThickness,
    right: borderThickness,
    background: colors.control_center
};

const project_config_options = {
    top: borderThickness,
    bottom: borderThickness,
    left: borderThickness,
    right: borderThickness,
    background: colors.project
};

const other_config_options = {
    top: borderThickness,
    bottom: borderThickness,
    left: borderThickness,
    right: borderThickness,
    background: colors.other
};


(async () => {
    const browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1865, height: 947 } });
    await context.addInitScript({
        path: './node_modules/mouse-helper/dist/mouse-helper.js'
    });
    const page = await context.newPage();

    // Login
    await page.goto(urlBase);
    await page.locator('#username').fill('admin');
    await page.locator('#password').fill('password');

    await page.click('#login_btn');

    for (const language of languages) {
        console.log(language.label);
        fs.mkdirSync(`screenshots/${language.code}`, { recursive: true });

        // Visit Control Center EM Manager Page
        await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/manager/control_center.php`);

        // Open External Modules Configuration Modal
        await page.locator('tr[data-module="security_access_groups"] button.external-modules-configure-button').click();

        // Select Language
        await page.locator('select[name="reserved-language-system"]').waitFor('visible');
        await page.locator('select[name="reserved-language-system"]').selectOption(language.selectValue);

        // Wait for all frames to load and then save changes
        const frames = page.frames();
        await Promise.all(frames.map(async frame => { frame.waitForLoadState('load') }));
        await page.locator('div#external-modules-configure-modal div.modal-footer button.save').click();


        // SHOT: cc_config
        console.log('\tcc_config');
        await page.locator('tr[data-module="security_access_groups"] button.external-modules-configure-button').click();
        await page.locator('tr[field="user-email-subject-template"]').scrollIntoViewIfNeeded();
        const cc_config = await page.locator('#external-modules-configure-modal .modal-dialog').screenshot();
        await sharp(cc_config).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_config.png`);

        // SHOT: cc_users
        console.log('\tcc_users');
        const dtInitPromise_cc_users = page.waitForFunction(() => { if ($.fn.dataTable.isDataTable('#SAG-System-Table')) return $('#SAG-System-Table').DataTable().data().count() > 0; });
        await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/?prefix=security_access_groups&page=system-settings-userlist`);
        await dtInitPromise_cc_users;
        const cc_window_cc_users = await page.locator('#control_center_window').boundingBox();
        const box_cc_users = await page.locator('div.SAG_Container').boundingBox();
        const cc_users = await page.screenshot({ clip: { x: cc_window_cc_users.x - 10, y: cc_window_cc_users.y - 10, width: cc_window_cc_users.width + 20, height: box_cc_users.y + box_cc_users.height + 30 } });
        await sharp(cc_users).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_users.png`);

        // SHOT: cc_users_actions
        console.log('\tcc_users_actions');
        await page.evaluate(() => {
            window['mouse-helper']();
        });
        await page.locator('div.SAG_Container button.dropdown-toggle').click();
        await page.locator('div.SAG_Container li:first-child a.dropdown-item').hover();
        const dropdown_cc_users_actions = await page.locator('div.SAG_Container li:first-child a.dropdown-item').boundingBox();
        await page.mouse.move(dropdown_cc_users_actions.x + dropdown_cc_users_actions.width - 10, dropdown_cc_users_actions.y + dropdown_cc_users_actions.height / 2);
        const cc_users_actions = await page.screenshot({ clip: { x: box_cc_users.x - 20, y: box_cc_users.y - 20, width: box_cc_users.width / 2, height: box_cc_users.height / 2 } });
        await sharp(cc_users_actions).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_users_actions.png`);

        // SHOT: cc_users_edit
        console.log('\tcc_users_edit');
        await page.locator('button.editUsersButton').click();
        await page.locator('tr[data-user="admin"] td:last-child span.select2').click();
        await page.locator('li[id$="sag_Default"]').hover();
        const option_cc_users_edit = await page.locator('li[id$="sag_Default"]').boundingBox();
        await page.mouse.move(option_cc_users_edit.x + option_cc_users_edit.width - 10, option_cc_users_edit.y + option_cc_users_edit.height / 2);
        const card_cc_users_edit = await page.locator('div.SAG_Container div.card').boundingBox();
        const cc_users_edit = await page.screenshot({ clip: { x: card_cc_users_edit.x - 20, y: card_cc_users_edit.y - 20, width: card_cc_users_edit.width + 40, height: card_cc_users_edit.height + 40 } });
        await sharp(cc_users_edit).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_users_edit.png`);

        // SHOT: cc_user_import_confirm
        console.log('\tcc_user_import_confirm');
        await page.addScriptTag({ content: `let window.shown = false;` });
        const waitForModal = page.waitForFunction(() => { $('.modal.fade.show').on('shown.bs.modal', () => window.shown = true); return window.shown; });
        await page.locator('#importUsersFile').setInputFiles('user_import.csv');
        await page.locator('div.modal.fade.show div.modal-lg.modal-dialog.modal-dialog-scrollable').waitFor('visible');
        await waitForModal;
        const box_cc_user_import_confirm = await page.locator('div.modal-lg.modal-dialog.modal-dialog-scrollable').boundingBox();
        const cc_user_import_confirm = await page.screenshot({ clip: { x: box_cc_user_import_confirm.x - 30, y: box_cc_user_import_confirm.y - 20, width: box_cc_user_import_confirm.width + 60, height: box_cc_user_import_confirm.height + 40 } });
        await sharp(cc_user_import_confirm).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_user_import_confirm.png`);

        // SHOT: cc_sags
        console.log('\tcc_sags');
        const dtInitPromise_cc_sags = page.waitForFunction(() => { if ($.fn.dataTable.isDataTable('table.sagTable')) return new $.fn.dataTable.Api('table.sagTable').data().count() > 0; });
        await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/?prefix=security_access_groups&page=system-settings-sags`);
        await dtInitPromise_cc_sags;
        const cc_window_cc_sags = await page.locator('#control_center_window').boundingBox();
        const box_cc_sags = await page.locator('div.SAG_Container').boundingBox();
        const cc_sags = await page.screenshot({ clip: { x: cc_window_cc_sags.x - 10, y: cc_window_cc_sags.y - 10, width: cc_window_cc_sags.width + 20, height: box_cc_sags.y + box_cc_sags.height + 30 } });
        await sharp(cc_sags).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_sags.png`);

        // SHOT: cc_sags_editor
        console.log('\tcc_sags_editor');
        await page.locator('a.SagLink', { hasText: "Default SAG" }).click();
        await page.locator('button#SAG_Delete').waitFor('visible');
        const box_cc_sags_editor = await page.locator('div#edit_sag_popup div.modal-content').boundingBox();
        const cc_sags_editor = await page.screenshot({ clip: { x: box_cc_sags_editor.x - 20, y: box_cc_sags_editor.y - 20, width: box_cc_sags_editor.width + 40, height: box_cc_sags_editor.height + 40 } });
        await sharp(cc_sags_editor).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_sags_editor.png`);
        await page.locator('button#SAG_Cancel').click();

        // SHOT: cc_sags_actions
        console.log('\tcc_sags_actions');
        await page.evaluate(() => {
            window['mouse-helper']();
        });
        await page.locator('div.SAG_Container button.btn.btn-primary.btn-xs.dropdown-toggle').click();
        await page.locator('div.SAG_Container li:first-child a.dropdown-item').hover();
        const dropdown_cc_sags_actions = await page.locator('div.SAG_Container li:first-child a.dropdown-item').boundingBox();
        await page.mouse.move(dropdown_cc_sags_actions.x + dropdown_cc_sags_actions.width - 10, dropdown_cc_sags_actions.y + dropdown_cc_sags_actions.height / 2);
        const box_cc_sags_actions_dropdown = await page.locator('div.SAG_Container div.container ul.dropdown-menu.show').boundingBox();
        const cc_sags_actions_bounds = {
            x: box_cc_sags.x - 20,
            y: box_cc_sags.y - 20,
            width: (box_cc_sags_actions_dropdown.x + box_cc_sags_actions_dropdown.width + 60) - box_cc_sags.x,
            height: (box_cc_sags_actions_dropdown.y + box_cc_sags_actions_dropdown.height + 60) - box_cc_sags.y
        }
        const cc_sags_actions = await page.screenshot({ clip: cc_sags_actions_bounds });
        await sharp(cc_sags_actions).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_sags_actions.png`);

        // SHOT: cc_sags_import_confirmation
        console.log('\tcc_sags_import_confirmation');
        await page.locator('#importSagsFile').setInputFiles('sag_import.csv');
        await page.locator('div.modal.fade.show div.modal-lg.modal-dialog.modal-dialog-scrollable button.btn-primary').waitFor('visible');
        const box_cc_sags_import_modal = await page.locator('div.modal-lg.modal-dialog.modal-dialog-scrollable').boundingBox();
        const cc_sags_import_confirmation = await page.screenshot({ clip: { x: box_cc_sags_import_modal.x - 30, y: box_cc_sags_import_modal.y - 20, width: box_cc_sags_import_modal.width + 60, height: box_cc_sags_import_modal.height + 40 } });
        await sharp(cc_sags_import_confirmation).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_sags_import_confirmation.png`);

        // SHOT: cc_report_types
        console.log('\tcc_report_types');
        page.goto(`${urlBase}/${redcapVersion}/ExternalModules/?prefix=security_access_groups&page=system-reports`);
        await page.locator('div.SAG_Container button.btn.btn-primary.btn-xs.dropdown-toggle').click();
        await page.locator('div.SAG_Container li:last-child a.dropdown-item').hover();
        const dropdown_cc_report_types = await page.locator('div.SAG_Container li:last-child a.dropdown-item').boundingBox();
        await page.evaluate(() => {
            window['mouse-helper']();
        });
        await page.mouse.move(dropdown_cc_report_types.x + dropdown_cc_report_types.width - 10, dropdown_cc_report_types.y + dropdown_cc_report_types.height / 2, { steps: 100 });
        const cc_window_cc_reports = await page.locator('#control_center_window').boundingBox();
        const box_cc_report_types_dropdown = await page.locator('div.SAG_Container ul.dropdown-menu.show').boundingBox();
        const cc_report_types_bounds = {
            x: cc_window_cc_reports.x - 20,
            y: cc_window_cc_reports.y - 20,
            width: cc_window_cc_reports.width,
            height: (box_cc_report_types_dropdown.y + box_cc_report_types_dropdown.height + 40) - cc_window_cc_reports.y
        };
        const cc_report_types = await page.screenshot({ clip: cc_report_types_bounds });
        await sharp(cc_report_types).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_report_types.png`);
        await page.mouse.move(0, 0);

        // SHOT: cc_report_example
        console.log('\tcc_report_example');
        await page.reload();
        await page.locator('div.SAG_Container button.btn.btn-primary.btn-xs.dropdown-toggle').click();
        await page.locator('div.SAG_Container li:last-child a.dropdown-item').click();
        await page.locator('div#allTableWrapper').waitFor('visible');
        await page.evaluate(() => {
            const el = document.querySelector('div.SAG_Container button.btn.btn-primary.btn-xs.dropdown-toggle');
            const nav = document.querySelector('nav.navbar.navbar-expand-md.navbar-light.fixed-top');
            const y = el.getBoundingClientRect().y - nav.getBoundingClientRect().height - 5;
            window.scrollTo(0, y);
        });
        await page.locator('div#allTableWrapper div.dataTables_filter input').click();
        const box_report_table_wrapper = await page.locator('div#allTableWrapper').boundingBox();
        const box_cc_report_types_button = await page.locator('div.SAG_Container button.btn.btn-primary.btn-xs.dropdown-toggle').boundingBox();
        await page.waitForTimeout(500);
        const cc_report_example = await page.screenshot({
            clip: {
                x: box_report_table_wrapper.x - 30,
                y: box_cc_report_types_button.y - 5,
                width: box_report_table_wrapper.width + 60,
                height: 900
            }
        });
        await sharp(cc_report_example).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_report_example.png`);

        // SHOT: cc_report_filter_example
        console.log('\tcc_report_filter_example');
        await page.locator('div#allTableWrapper div.dataTables_filter input').fill('project_status=Production');
        const cc_report_filter_example = await page.screenshot({
            clip: {
                x: box_report_table_wrapper.x - 30,
                y: box_cc_report_types_button.y - 5,
                width: box_report_table_wrapper.width + 60,
                height: 900
            }
        });
        await sharp(cc_report_filter_example).extend(cc_config_options).toFile(`${screenshotsPath}/${language.code}/cc_report_filter_example.png`);


    }
    // Go to Control Center
    // await page.getByText('Control Center').click();
    // await page.waitForURL('**/ControlCenter/index.php');

    await browser.close();
})();