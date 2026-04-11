/**
 * ColdForge Warming Bot — Login Script v3
 * 
 * Opens a VISIBLE browser window so the user can manually log into Zoho Mail.
 * Watches ALL tabs for successful login detection.
 * The session is saved in a persistent userDataDir.
 * 
 * Usage:
 *   node login.js --account-id=1
 *   node login.js --account-id=1 --session-dir="C:/custom/path"
 */

const puppeteer = require('puppeteer');
const axios = require('axios');
const path = require('path');
const fs = require('fs');

const LARAVEL_URL = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';

// Parse command line arguments
const args = {};
process.argv.slice(2).forEach(arg => {
    const match = arg.match(/^--([^=]+)(?:=(.*))?$/);
    if (match) {
        args[match[1]] = match[2] !== undefined ? match[2] : true;
    }
});

const accountId = args['account-id'];
let sessionDir = args['session-dir'];

if (!accountId) {
    console.error('Usage: node login.js --account-id=<ID> [--session-dir=<PATH>]');
    process.exit(1);
}

// Default session dir if not provided
if (!sessionDir) {
    sessionDir = path.join(__dirname, '..', 'storage', 'app', 'warming_sessions', String(accountId));
}

// Ensure session directory exists
if (!fs.existsSync(sessionDir)) {
    fs.mkdirSync(sessionDir, { recursive: true });
}

(async () => {
    console.log('');
    console.log('╔══════════════════════════════════════════╗');
    console.log('║   🔐 ColdForge — Zoho Login Helper      ║');
    console.log('╚══════════════════════════════════════════╝');
    console.log(`   Account ID:  #${accountId}`);
    console.log(`   Session Dir: ${sessionDir}`);
    console.log('');

    let browser;
    try {
        browser = await puppeteer.launch({
            headless: false, // MUST be visible so user can log in
            userDataDir: sessionDir,
            defaultViewport: null, // Use full window size
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled',
                '--start-maximized',
            ],
        });

        // Hide webdriver detection on ALL new pages
        browser.on('targetcreated', async (target) => {
            const page = await target.page();
            if (page) {
                await page.evaluateOnNewDocument(() => {
                    Object.defineProperty(navigator, 'webdriver', { get: () => false });
                }).catch(() => {});
            }
        });

        const pages = await browser.pages();
        const page = pages[0] || await browser.newPage();

        await page.evaluateOnNewDocument(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => false });
        });

        // Navigate to Zoho Mail
        console.log('📧 Opening Zoho Mail...');
        await page.goto('https://mail.zoho.com', {
            waitUntil: 'networkidle2',
            timeout: 60000,
        });

        const currentUrl = page.url();
        console.log(`   Current URL: ${currentUrl}`);

        // Check if already in the mail app on this tab
        if (currentUrl.includes('/zm/') || currentUrl.includes('/zm#')) {
            console.log('');
            console.log('✅ You are ALREADY logged in! Session is valid.');
            await notifyLaravel(accountId);
            console.log('🔒 Closing browser in 3 seconds...');
            await sleep(3000);
            await browser.close();
            return;
        }

        console.log('');
        console.log('═══════════════════════════════════════════');
        console.log('  📝 Please log in to Zoho Mail now.');
        console.log('  ⏳ This window will close automatically');
        console.log('     once you reach your inbox.');
        console.log('  ⏰ Timeout: 5 minutes');
        console.log('═══════════════════════════════════════════');
        console.log('');

        // Poll ALL open tabs for /zm/ URL
        const maxWait = 300000; // 5 minutes
        const startTime = Date.now();
        let loggedIn = false;

        while (!loggedIn && Date.now() - startTime < maxWait) {
            try {
                // Check ALL open pages/tabs in the browser
                const allPages = await browser.pages();
                for (const p of allPages) {
                    try {
                        const url = p.url();
                        if (url.includes('/zm/') || url.includes('/zm#')) {
                            // Found the inbox! Wait a moment to let it settle
                            console.log(`   🔍 Detected inbox URL: ${url}`);
                            await sleep(2000);
                            loggedIn = true;
                            break;
                        }
                    } catch (e) {
                        // Page might be navigating or closed
                    }
                }
            } catch (e) {
                // Browser might be in a transitional state
            }

            if (!loggedIn) {
                await sleep(2000);
            }
        }

        if (loggedIn) {
            console.log('');
            console.log('✅ Login detected! You are now in the Zoho Mail inbox.');
            console.log('   Session cookies have been saved to disk.');
            await notifyLaravel(accountId);
        } else {
            console.log('');
            console.log('⏰ Timeout — login was not completed within 5 minutes.');
            console.log('   Please try again.');
        }

        console.log('');
        console.log('🔒 Closing browser in 5 seconds...');
        await sleep(5000);
        await browser.close();

    } catch (error) {
        console.error('❌ Error:', error.message);
        if (browser) {
            try { await browser.close(); } catch (e) { /* ignore */ }
        }
        process.exit(1);
    }

    console.log('✅ Done.');
})();

/**
 * Notify Laravel that the account is now logged in.
 */
async function notifyLaravel(accountId) {
    try {
        await axios.post(`${LARAVEL_URL}/api/warming/mark-logged-in`, {
            account_id: parseInt(accountId),
        });
        console.log('📡 Laravel notified — account marked as logged in.');
    } catch (err) {
        console.error('⚠️  Could not notify Laravel:', err.message);
        console.log('   The login session IS saved locally.');
        console.log('   You may need to refresh the accounts page.');
    }
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}
