const puppeteer = require('puppeteer');
const fs = require('fs');

const SESSION_DIR = "C:\\Users\\Mohammed\\.gemini\\antigravity\\scratch\\cold-email-generator\\storage\\app\\warming_sessions\\2";

(async () => {
    console.log('Launching browser...');
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
    
    // Focus Custom Radio
    console.log('Opening Custom Date Menu...');
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
            radios[radios.length - 1].focus();
        }
    });

    await new Promise(r => setTimeout(r, 1000));
    
    async function debugActive(step) {
        const html = await page.evaluate(() => {
            const e = document.activeElement;
            if(!e) return 'none';
            return `<${e.tagName} class="${e.className}" type="${e.type}" value="${e.value}" placeholder="${e.placeholder}">${e.innerText || ''}</${e.tagName}>`;
        });
        console.log(`[${step}] Active: ${html}`);
        await page.screenshot({ path: `C:\\Users\\Mohammed\\.gemini\\antigravity\\scratch\\cold-email-generator\\debug_${step}.png` });
    }

    await debugActive('0_After_Custom_Click');

    // 1. Press Tab
    await page.keyboard.press('Tab');
    await new Promise(r => setTimeout(r, 500));
    await debugActive('1_After_Tab_1');

    // 2. Press Space (Open calendar?)
    await page.keyboard.press('Space');
    await new Promise(r => setTimeout(r, 500));
    await debugActive('2_After_Space');

    // 3. ArrowRight 2 times (Days)
    await page.keyboard.press('ArrowRight');
    await new Promise(r => setTimeout(r, 300));
    await page.keyboard.press('ArrowRight');
    await new Promise(r => setTimeout(r, 300));
    await debugActive('3_After_ArrowRight');

    // 4. Space (Confirm Day)
    await page.keyboard.press('Space');
    await new Promise(r => setTimeout(r, 500));
    await debugActive('4_After_Space_Confirm');

    // 5. Tab
    await page.keyboard.press('Tab');
    await new Promise(r => setTimeout(r, 500));
    await debugActive('5_After_Tab_Hours');

    // 6. Tab
    await page.keyboard.press('Tab');
    await new Promise(r => setTimeout(r, 500));
    await debugActive('6_After_Tab_Minutes');

    console.log('Done!');
    await browser.close();
})();
