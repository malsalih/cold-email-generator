const puppeteer = require('puppeteer');
const { log, error, isDev } = require('./logger');

class BrowserManager {
    constructor() {
        this.browser = null;
        this.sessionDir = null;
    }

    async getBrowser(jobSessionDir, isHeadless) {
        if (this.browser && this.browser.connected && this.sessionDir === jobSessionDir) {
            return this.browser;
        }

        if (this.browser) {
            try { await this.browser.close(); } catch (e) { /* ignore */ }
            this.browser = null;
        }

        this.sessionDir = jobSessionDir;

        log(`🌐 Launching browser (${isHeadless ? 'headless' : 'visible'})...`, true);

        this.browser = await puppeteer.launch({
            headless: isHeadless ? 'new' : false,
            userDataDir: this.sessionDir,
            defaultViewport: null,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
                ...(isHeadless ? [] : ['--start-maximized']),
            ],
        });

        this.browser.on('targetcreated', async (target) => {
            const page = await target.page().catch(() => null);
            if (page) {
                await page.evaluateOnNewDocument(() => {
                    Object.defineProperty(navigator, 'webdriver', { get: () => false });
                }).catch(() => {});
            }
        });

        this.browser.on('disconnected', () => {
            error('Browser disconnected unexpectedly', null, true);
            this.browser = null;
        });

        return this.browser;
    }

    async closeBrowser() {
        if (this.browser) {
            try { await this.browser.close(); } catch (e) { /* ignore */ }
            this.browser = null;
            log('Browser closed cleanly.');
        }
    }
}

module.exports = new BrowserManager();
