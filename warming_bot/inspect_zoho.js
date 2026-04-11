const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const SESSION_DIR = "C:\\Users\\Mohammed\\.gemini\\antigravity\\scratch\\cold-email-generator\\storage\\app\\warming_sessions\\2";

(async () => {
    console.log('Launching browser with session:', SESSION_DIR);
    const browser = await puppeteer.launch({
        headless: 'new',
        userDataDir: SESSION_DIR,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--window-size=1280,800']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });

    console.log('Navigating to Zoho...');
    await page.goto('https://mail.zoho.com/zm/#compose', { waitUntil: 'networkidle2', timeout: 30000 });
    
    await new Promise(r => setTimeout(r, 5000));
    console.log('Clicking Send Later...');

    // Click Send Later
    await page.evaluate(() => {
        const els = Array.from(document.querySelectorAll('button, a, span, div, li'));
        els.sort((a, b) => a.children.length - b.children.length);
        for (const el of els) {
            const text = el.textContent.trim();
            if ((text === 'Send Later' || text.match(/Send Later/i)) && el.offsetHeight > 0) {
                if (el.children.length < 4) {
                    el.click();
                    return true;
                }
            }
        }
    });

    await new Promise(r => setTimeout(r, 2000));
    console.log('Taking modal screenshot...');
    await page.screenshot({ path: 'C:\\Users\\Mohammed\\.gemini\\antigravity\\scratch\\cold-email-generator\\zoho_modal_base.png' });

    console.log('Clicking Custom Date and Time...');
    await page.evaluate(() => {
        const els = Array.from(document.querySelectorAll('li, div, span, label, a, button, td'));
        els.sort((a, b) => a.children.length - b.children.length);
        for (const el of els) {
            const text = el.textContent?.trim() || '';
            if (text.match(/Custom Date/i) && el.offsetHeight > 0) {
                if (el.children.length < 5) {
                    el.click();
                    return true;
                }
            }
        }
        const radios = document.querySelectorAll('input[type="radio"]');
        if (radios.length > 0) {
            radios[radios.length - 1].click();
        }
    });

    await new Promise(r => setTimeout(r, 2000));
    console.log('Taking full modal screenshot & HTML...');
    await page.screenshot({ path: 'C:\\Users\\Mohammed\\.gemini\\antigravity\\scratch\\cold-email-generator\\zoho_custom_date.png' });

    const html = await page.evaluate(() => {
        const dialog = document.querySelector('[role="dialog"], .modal, [class*="dialog"], [class*="schedule"]') || document.body;
        return dialog.outerHTML;
    });

    fs.writeFileSync('C:\\Users\\Mohammed\\.gemini\\antigravity\\scratch\\cold-email-generator\\zoho_custom_date.html', html);
    console.log('Done!');

    await browser.close();
})();
