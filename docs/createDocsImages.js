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

const shotsToTake = "all"; // "project", "control_center", "other", "all"

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

const user_email_body_template = '<p>[sag-user-fullname],</p><p>This message is an alert that the current User Rights configuration in project [project-id] ([sag-project-title]) are out of compliance with your current Security Access Group: [sag-user-sag].</p><p><strong>Username</strong>: [sag-user]</p><p>These rights are out of compliance:<br />[sag-rights]</p>';
const user_reminder_email_body_template = '<p>[sag-user-fullname],</p><p>This message is a reminder that the current User Rights configuration in project [project-id] ([sag-project-title]) are out of compliance with your current Security Access Group: [sag-user-sag].</p><p><strong>Username</strong>: [sag-user]</p><p>These rights are out of compliance:<br />[sag-rights]</p>';
const user_rights_holders_email_body_template = '<p>Hello,</p><p>This message is an alert that the current User Rights configuration in project [project-id] are out of compliance with the Security Access Groups of the following users.</p><p>[sag-users-table-full]</p><p>Please take appropriate action to address these noncompliant rights.</p>';
const user_rights_holders_reminder_email_body_template = '<p>Hello,</p><p>This message is a reminder that the current User Rights configuration in project [project-id] are out of compliance with the Security Access Groups of the following users.</p><p>[sag-users-table-full]</p><p>Please take appropriate action to address these noncompliant rights.</p>';
const expiration_user_email_body_template = '';
const expiration_user_rights_holders_email_body_template = '';

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


        ////////////////////////
        //    SET LANGUAGE    //
        ////////////////////////

        // Visit Control Center EM Manager Page
        await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/manager/control_center.php`);

        // Open External Modules Configuration Modal
        await page.locator('tr[data-module="security_access_groups"] button.external-modules-configure-button').click();

        // Select Language
        await page.locator('select[name="reserved-language-system"]').waitFor('visible');
        await page.locator('select[name="reserved-language-system"]').selectOption(language.selectValue);

        // We had some issues with the templates being erased, so make sure they're set.
        const frames = page.frames();
        await Promise.all(frames.map(async frame => { frame.waitForLoadState('load') }));
        await page.evaluate(([user, user_reminder, urh, urh_reminder, expiration, expiration_urh]) => {
            tinymce.get($('textarea[name="user-email-body-template"]').attr('id')).setContent(user);
            tinymce.get($('textarea[name="user-reminder-email-body-template"]').attr('id')).setContent(user_reminder);
            tinymce.get($('textarea[name="user-rights-holders-email-body-template"]').attr('id')).setContent(urh);
            tinymce.get($('textarea[name="user-rights-holders-reminder-email-body-template"]').attr('id')).setContent(urh_reminder);
            tinymce.get($('textarea[name="user-expiration-email-body-template"]').attr('id')).setContent(expiration);
            tinymce.get($('textarea[name="user-expiration-user-rights-holders-email-body-template"]').attr('id')).setContent(expiration_urh);
        }, [user_email_body_template, user_reminder_email_body_template, user_rights_holders_email_body_template, user_rights_holders_reminder_email_body_template, expiration_user_email_body_template, expiration_user_rights_holders_email_body_template]);

        // Save and settle
        await page.locator('div#external-modules-configure-modal div.modal-footer button.save').click();
        await page.waitForURL('**/ExternalModules/manager/control_center.php', { waitUntil: 'networkidle' });

        ////////////////////////////
        //     CONTROL CENTER     //
        ////////////////////////////

        if (shotsToTake === "control_center" || shotsToTake === "all") {
            console.log('  --control center--');

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

        /////////////////////
        //     PROJECT     //
        /////////////////////

        if (shotsToTake === "project" || shotsToTake === "all") {
            console.log('  --project--');

            // SHOT: p_status
            console.log('\tp_status');
            const dtInitPromise_p_status = page.waitForFunction(() => { if ($.fn.dataTable.isDataTable('#discrepancy-table')) return $('#discrepancy-table').DataTable().data().count() > 0; });
            await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/?prefix=security_access_groups&page=project-status&pid=${projectId}`);
            await dtInitPromise_p_status;
            const box_sag_container = await page.locator('div.SAG-Container').boundingBox();
            const box_p_status_table = await page.locator('div#containerCard').boundingBox();
            const p_status = await page.screenshot({
                clip: {
                    x: box_sag_container.x - 20,
                    y: box_sag_container.y - 20,
                    width: box_p_status_table.width + 40,
                    height: box_sag_container.height + 40
                }
            });
            await sharp(p_status).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status.png`);

            // SHOT: p_status_alert_user
            console.log('\tp_status_alert_user');
            await page.locator('tr[data-user="admin"] td:first-child input').check();
            await page.evaluate(() => { window.shown = false; });
            const waitForModal_p_user = page.waitForFunction(() => { $('#emailUsersModal').on('shown.bs.modal', () => window.shown = true); return window.shown; });
            await page.locator('div#containerCard div.buttonContainer button.btn.btn-primary').click();
            await waitForModal_p_user;
            const box_p_status_alert_user = await page.locator('div#emailUsersModal div.modal-content').boundingBox();
            const p_status_alert_user = await page.screenshot({ clip: { x: box_p_status_alert_user.x - 30, y: box_p_status_alert_user.y - 20, width: box_p_status_alert_user.width + 60, height: box_p_status_alert_user.height + 40 } });
            await sharp(p_status_alert_user).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_alert_user.png`);

            // SHOT: p_status_alert_user_reminder
            console.log('\tp_status_alert_user_reminder');
            await page.locator('div#emailUsersModal input#sendReminder').check();
            await page.waitForTimeout(500);
            const placeholders = page.locator('div#emailUsersModal div#reminderInfo table[aria-label="placeholders"]');
            await placeholders.waitFor('visible');
            await placeholders.scrollIntoViewIfNeeded();
            const p_status_alert_user_reminder = await page.screenshot({ clip: { x: box_p_status_alert_user.x - 30, y: box_p_status_alert_user.y - 20, width: box_p_status_alert_user.width + 60, height: box_p_status_alert_user.height + 40 } });
            await sharp(p_status_alert_user_reminder).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_alert_user_reminder.png`);
            await page.locator('div#emailUsersModal button.btn-close').click();

            // SHOT: p_status_alert_user_rights_holder
            console.log('\tp_status_alert_user_rights_holder');
            await page.evaluate(() => { window.shown = false; });
            const waitForModal_p_user_rights_holder = page.waitForFunction(() => { $('#emailUserRightsHoldersModal').on('shown.bs.modal', () => window.shown = true); return window.shown; });
            await page.locator('div#containerCard div.buttonContainer button.btn.btn-warning').click();
            await waitForModal_p_user_rights_holder;
            const box_p_status_alert_user_rights_holder = await page.locator('div#emailUserRightsHoldersModal div.modal-content').boundingBox();
            const p_status_alert_user_rights_holder = await page.screenshot({ clip: { x: box_p_status_alert_user_rights_holder.x - 30, y: box_p_status_alert_user_rights_holder.y - 20, width: box_p_status_alert_user_rights_holder.width + 60, height: box_p_status_alert_user_rights_holder.height + 40 } });
            await sharp(p_status_alert_user_rights_holder).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_alert_user_rights_holder.png`);

            // SHOT: p_status_alert_user_rights_holder_reminder
            console.log('\tp_status_alert_user_rights_holder_reminder');
            await page.locator('div#emailUserRightsHoldersModal input#sendReminder-UserRightsHolders').check();
            await page.waitForTimeout(500);
            const label_p_status_alert_user_rights_holder_reminder = page.locator('div#emailUserRightsHoldersModal div.reminderEmail-UserRightsHolders label[for="reminderBody-UserRightsHolders"]');
            await label_p_status_alert_user_rights_holder_reminder.waitFor('visible');
            await label_p_status_alert_user_rights_holder_reminder.scrollIntoViewIfNeeded();
            const p_status_alert_user_rights_holder_reminder = await page.screenshot({ clip: { x: box_p_status_alert_user_rights_holder.x - 30, y: box_p_status_alert_user_rights_holder.y - 20, width: box_p_status_alert_user_rights_holder.width + 60, height: box_p_status_alert_user_rights_holder.height + 40 } });
            await sharp(p_status_alert_user_rights_holder_reminder).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_alert_user_rights_holder_reminder.png`);
            await page.locator('div#emailUserRightsHoldersModal button.btn-close').click();

            // SHOT: p_status_expiration
            console.log('\tp_status_expiration');
            await page.evaluate(() => { window.shown = false; });
            const waitForModal_p_expiration = page.waitForFunction(() => { $('#userExpirationModal').on('shown.bs.modal', () => window.shown = true); return window.shown; });
            await page.locator('div#containerCard div.buttonContainer button.btn.btn-danger').click();
            await waitForModal_p_expiration;
            const box_p_status_expiration = await page.locator('div#userExpirationModal div.modal-content').boundingBox();
            const p_status_expiration = await page.screenshot({ clip: { x: box_p_status_expiration.x - 30, y: box_p_status_expiration.y - 20, width: box_p_status_expiration.width + 60, height: box_p_status_expiration.height + 40 } });
            await sharp(p_status_expiration).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_expiration.png`);

            // SHOT: p_status_expiration_alert_user
            console.log('\tp_status_expiration_alert_user');
            await page.locator('div#userExpirationModal input#sendUserNotification').check();
            await page.waitForTimeout(500);
            const button_p_status_expiration_alert_user = page.locator('div#userExpirationModal div#userNotificationInfo button.btn.btn-info');
            const box_p_status_expiration_user = await page.locator('div#userExpirationModal div.modal-content').boundingBox();
            await button_p_status_expiration_alert_user.waitFor('visible');
            await button_p_status_expiration_alert_user.scrollIntoViewIfNeeded();
            const p_status_expiration_alert_user = await page.screenshot({ clip: { x: box_p_status_expiration.x - 30, y: box_p_status_expiration.y - 20, width: box_p_status_expiration_user.width + 60, height: box_p_status_expiration_user.height + 40 } });
            await sharp(p_status_expiration_alert_user).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_expiration_alert_user.png`);

            // SHOT: p_status_expiration_alert_user-rights-holder
            console.log('\tp_status_expiration_alert_user-rights-holder');
            await page.locator('div#userExpirationModal input#sendNotification-userExpiration-UserRightsHolders').check();
            await page.waitForTimeout(500);
            const text_p_status_expiration_alert_user_rights_holder = page.locator('div#userExpirationModal div#notificationInfo-userExpiration-UserRightsHolders code', { hasText: "[sag-users]" });
            const box_p_status_expiration_user_rights_holder = await page.locator('div#userExpirationModal div.modal-content').boundingBox();
            await text_p_status_expiration_alert_user_rights_holder.waitFor('visible');
            await page.evaluate(() => { $('div#userExpirationModal .modal-body')[0].scrollTo(0, $('#expireUsersUserRightsHolderSelection')[0].getBoundingClientRect().top + 200); });
            await page.waitForTimeout(500);
            const p_status_expiration_alert_user_rights_holder = await page.screenshot({ clip: { x: box_p_status_expiration_user_rights_holder.x - 30, y: box_p_status_expiration_user_rights_holder.y - 20, width: box_p_status_expiration_user_rights_holder.width + 60, height: box_p_status_expiration_user_rights_holder.height + 40 } });
            await sharp(p_status_expiration_alert_user_rights_holder).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_status_expiration_alert_user-rights-holder.png`);

            // SHOT p_alert_log
            console.log('\tp_alert_log');
            const dtInitPromise_p_alerts = page.waitForFunction(() => { if ($.fn.dataTable.isDataTable('#alertLogTable')) return $('#alertLogTable').DataTable().data().count() > 0; });
            await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/?prefix=security_access_groups&page=project-alert-log&pid=${projectId}`);
            await dtInitPromise_p_alerts;
            await page.evaluate(() => { window.scrollTo(0, $('.SAG-Container')[0].getBoundingClientRect().top - 20) });
            const box_p_alert_log = await page.locator('div.SAG-Container').boundingBox();
            const box_p_alert_log_wrapper = await page.locator('div.alertLogWrapper').boundingBox();
            const p_alert_log = await page.screenshot({
                clip: {
                    x: box_p_alert_log.x - 20,
                    y: box_p_alert_log.y - 20,
                    width: box_p_alert_log_wrapper.width + 40,
                    height: box_p_alert_log.height + 40
                }
            });
            await sharp(p_alert_log).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_alert_log.png`);

            // SHOT: p_logs_preview
            console.log('\tp_logs_preview');
            await page.evaluate(() => { sag_module.openAlertPreview(37212); });
            await page.locator('#alertPreviewModal').waitFor('visible');
            await page.waitForTimeout(500);
            const box_alert_preview_modal = await page.locator('#alertPreviewModal .modal-content').boundingBox();
            const p_logs_preview = await page.screenshot({ clip: { x: box_alert_preview_modal.x - 30, y: box_alert_preview_modal.y - 20, width: box_alert_preview_modal.width + 60, height: box_alert_preview_modal.height + 40 } });
            await sharp(p_logs_preview).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_logs_preview.png`);


            // SHOT: p_blocked
            console.log('\tp_blocked');
            await page.goto(`${urlBase}/${redcapVersion}/UserRights/index.php?pid=${projectId}`);
            await page.waitForURL(`${urlBase}/${redcapVersion}/UserRights/index.php?pid=${projectId}`);
            await page.locator('a.userRightsExpired[userid="bob"]').click();
            await page.locator('div#userClickExpiration').waitFor('visible');
            await page.locator('input#tooltipExpiration').clear();
            await page.locator('button#tooltipExpirationBtn').click();
            await page.locator('div.swal2-popup.swal2-show').waitFor('visible');
            await page.waitForTimeout(500);
            const box_p_blocked = await page.locator('div.swal2-popup.swal2-show').boundingBox();
            const p_blocked = await page.screenshot({ clip: { x: box_p_blocked.x - 30, y: box_p_blocked.y - 20, width: box_p_blocked.width + 60, height: box_p_blocked.height + 40 } });
            await sharp(p_blocked).extend(project_config_options).toFile(`${screenshotsPath}/${language.code}/p_blocked.png`);

            // SHOT: p_logging_user
            console.log('\tp_logging_user');
            const log_url1 = `${urlBase}/${redcapVersion}/Logging/index.php?pid=${projectId}&beginTime=07/23/2023%2018:20&endTime=07/23/2023%2018:22&usr=admin&record=&logtype=user&dag=`;
            await page.goto(log_url1);
            await page.waitForURL(log_url1, { waitUntil: 'networkidle' });
            const td_log_user = await page.locator('td[ts="20230723182103"]').first().boundingBox();
            const box_log_user = await page.locator('table[logeventtable="redcap_log_event6"]').boundingBox();
            const p_logging_user = await page.screenshot({ clip: { x: box_log_user.x, y: td_log_user.y, width: box_log_user.width, height: td_log_user.height } });
            await sharp(p_logging_user).extend(project_config_options).toFile(`${screenshotsPath}/EN/p_logging_user.png`);

            // SHOT: p_logging_role
            console.log('\tp_logging_role');
            const log_url2 = `${urlBase}/${redcapVersion}/Logging/index.php?pid=${projectId}&beginTime=07/21/2023%2016:41&endTime=07/21/2023%2016:43&usr=admin&record=&logtype=user&dag=`;
            await page.goto(log_url2);
            await page.waitForURL(log_url2, { waitUntil: 'networkidle' });
            const td_log_role = await page.locator('td[ts="20230721164250"]').first().boundingBox();
            const box_log_role = await page.locator('table[logeventtable="redcap_log_event6"]').boundingBox();
            const p_logging_role = await page.screenshot({ clip: { x: box_log_role.x, y: td_log_role.y, width: box_log_role.width, height: td_log_role.height } });
            await sharp(p_logging_role).extend(project_config_options).toFile(`${screenshotsPath}/EN/p_logging_role.png`);
        }

        ///////////////////
        //     Other     //
        ///////////////////

        if (shotsToTake === "other" || shotsToTake === "all") {
            console.log('  --other--');

            // SHOT: searching_example
            console.log('\tsearching_example');
            const dtInitPromise_searching = page.waitForFunction(() => { if ($.fn.dataTable.isDataTable('#SAG-System-Table')) return $('#SAG-System-Table').DataTable().data().count() > 0; });
            await page.goto(`${urlBase}/${redcapVersion}/ExternalModules/?prefix=security_access_groups&page=system-settings-userlist`);
            await dtInitPromise_searching;
            await page.locator('div.SAG_Container div.dataTables_filter input').fill('Nothing');
            await page.waitForTimeout(500);
            const box_searching_example = await page.locator('div.SAG_Container div.card').boundingBox();
            const searching_example = await page.screenshot({ clip: { x: box_searching_example.x - 20, y: box_searching_example.y - 20, width: box_searching_example.width + 40, height: box_searching_example.height + 40 } });
            await sharp(searching_example).extend(other_config_options).toFile(`${screenshotsPath}/${language.code}/searching_example.png`);


            // SHOT: searching_example_AND
            console.log('\tsearching_example_AND');
            await page.locator('div.SAG_Container div.dataTables_filter input').fill('joe admin');
            await page.waitForTimeout(500);
            const box_searching_example_AND = await page.locator('div.SAG_Container div.card').boundingBox();
            const searching_example_AND = await page.screenshot({ clip: { x: box_searching_example_AND.x - 20, y: box_searching_example_AND.y - 20, width: box_searching_example_AND.width + 40, height: box_searching_example_AND.height + 40 } });
            await sharp(searching_example_AND).extend(other_config_options).toFile(`${screenshotsPath}/${language.code}/searching_example_AND.png`);

            // SHOT: searching_example_exact_phrase
            console.log('\tsearching_example_exact_phrase');
            await page.locator('div.SAG_Container div.dataTables_filter input').fill('"joe admin"');
            await page.waitForTimeout(500);
            const box_searching_example_exact_phrase = await page.locator('div.SAG_Container div.card').boundingBox();
            const searching_example_exact_phrase = await page.screenshot({ clip: { x: box_searching_example_exact_phrase.x - 20, y: box_searching_example_exact_phrase.y - 20, width: box_searching_example_exact_phrase.width + 40, height: box_searching_example_exact_phrase.height + 40 } });
            await sharp(searching_example_exact_phrase).extend(other_config_options).toFile(`${screenshotsPath}/${language.code}/searching_example_exact_phrase.png`);

            // SHOT: searching_example_OR
            console.log('\tsearching_example_OR');
            await page.locator('div.SAG_Container div.dataTables_filter input').fill('alice | bob');
            await page.waitForTimeout(500);
            const box_searching_example_OR = await page.locator('div.SAG_Container div.card').boundingBox();
            const searching_example_OR = await page.screenshot({ clip: { x: box_searching_example_OR.x - 20, y: box_searching_example_OR.y - 20, width: box_searching_example_OR.width + 40, height: box_searching_example_OR.height + 40 } });
            await sharp(searching_example_OR).extend(other_config_options).toFile(`${screenshotsPath}/${language.code}/searching_example_OR.png`);

            // SHOT: searching_example_regular_expression
            console.log('\tsearching_example_regular_expression');
            await page.locator('div.SAG_Container div.dataTables_filter input').fill('| d[a-z]*n');
            await page.waitForTimeout(500);
            const box_searching_example_regular_expression = await page.locator('div.SAG_Container div.card').boundingBox();
            const searching_example_regular_expression = await page.screenshot({ clip: { x: box_searching_example_regular_expression.x - 20, y: box_searching_example_regular_expression.y - 20, width: box_searching_example_regular_expression.width + 40, height: box_searching_example_regular_expression.height + 40 } });
            await sharp(searching_example_regular_expression).extend(other_config_options).toFile(`${screenshotsPath}/${language.code}/searching_example_regular_expression.png`);

        }
    }
    // Go to Control Center
    // await page.getByText('Control Center').click();
    // await page.waitForURL('**/ControlCenter/index.php');

    await browser.close();
})();