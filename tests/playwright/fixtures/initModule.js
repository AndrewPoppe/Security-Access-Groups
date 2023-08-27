const { test: base, expect } = require('@playwright/test');
const { Module } = require('./module');
const { config } = require('./config');

exports.test = base.extend({
    setupPage: [async ({ page }, use) => {
        const module = new Module(page, {
            redcapVersion: config.redcapVersion,
            baseUrl: config.redcapUrl,
            username: config.users.AdminUser.username,
            password: config.users.AdminUser.password,
        });
        await module.logIn();
        await module.setLanguageToEnglish();
        await module.visitSAGsPage();
        await module.deleteExistingSAGs();
        module.settings.nothingSagId = await module.createNothingSAG();
        module.settings.everythingSagId = await module.createEverythingSAG();
        await module.setUserSAG(config.users.NothingUser.username, module.settings.nothingSagId);
        await module.setUserSAG(config.users.EverythingUser.username, module.settings.everythingSagId);
        await use(module);
    }, { timeout: 300000 }],
    modulePage: async ({ page }, use) => {
        const module = new Module(page, {
            redcapVersion: config.redcapVersion,
            baseUrl: config.redcapUrl,
            username: config.users.AdminUser.username,
            password: config.users.AdminUser.password,
        });
        await module.logIn();
        await use(module);
    }
});
exports.expect = expect;