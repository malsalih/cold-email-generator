/**
 * ColdForge Warming Bot v4 — Safety-First Architecture
 * 
 * Features:
 * - Live status reporting to Laravel dashboard
 * - Field verification before send
 * - Double-send prevention (verify-job check)
 * - Auto-stop after consecutive failures
 * - Session-based logging
 * 
 * Usage:
 *   node bot.js                         # Visible browser, process all pending
 *   node bot.js --loop                  # Continuous loop
 *   node bot.js --loop --manual         # Loop but wait for user to click Send
 *   node bot.js --headless              # Headless mode (for servers)
 *   node bot.js --loop --interval=300   # Custom polling interval (seconds)
 */

const puppeteer = require('puppeteer');
const axios = require('axios');

const LARAVEL_URL = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
const SESSION_ID = `bot_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;

// ─── Parse Arguments ─────────────────────────────────────────

const args = {};
process.argv.slice(2).forEach(arg => {
    const match = arg.match(/^--([^=]+)(?:=(.*))?$/);
    if (match) args[match[1]] = match[2] !== undefined ? match[2] : true;
});

const isLoop = !!args.loop;
const isHeadless = !!args.headless;
const cliManual = !!args.manual;
const cliSkipFill = !!args['skip-fill'];
const cliSendLater = !!args['send-later'];  // Campaign Send Later mode
const cliTimezone = args.timezone || null;   // Timezone for Send Later
const intervalSeconds = parseInt(args.interval) || 120;
const accountId = args.account || null; // Filter: only process this account's jobs

// ─── Safety Counters ─────────────────────────────────────────

let consecutiveFailures = 0;
const MAX_CONSECUTIVE_FAILURES = 5;
let totalSentThisSession = 0;
const MAX_PER_SESSION = 50;

// ─── Laravel API Functions ───────────────────────────────────

async function getNextJob() {
    try {
        const params = accountId ? { account_id: accountId } : {};
        if (cliSendLater) params.mode = 'send_later';
        const { data } = await axios.get(`${LARAVEL_URL}/api/warming/next-job`, { params });
        return data;
    } catch (err) {
        log('❌ Laravel API unreachable: ' + err.message);
        return { has_job: false, reason: 'API unreachable' };
    }
}

async function reportResult(logId, status, errorMessage = null) {
    try {
        await axios.post(`${LARAVEL_URL}/api/warming/report`, {
            log_id: logId,
            status,
            error_message: errorMessage,
        });
    } catch (err) {
        log('⚠️  Failed to report: ' + err.message);
    }
}

async function verifyJob(logId) {
    try {
        const { data } = await axios.get(`${LARAVEL_URL}/api/warming/verify-job/${logId}`);
        return data;
    } catch (err) {
        log('⚠️  Job verify failed: ' + err.message);
        return { valid: false, reason: 'API error' };
    }
}

async function pushBotLog(event, message, logId = null, metadata = null) {
    try {
        await axios.post(`${LARAVEL_URL}/api/warming/bot-log`, {
            event,
            message,
            log_id: logId,
            metadata,
            session_id: SESSION_ID,
        });
    } catch (err) {
        // Silent — don't break bot flow for logging failures
    }
}

// ─── Browser Manager ─────────────────────────────────────────

let browser = null;
let sessionDir = null;

async function getBrowser(jobSessionDir) {
    if (browser && browser.connected && sessionDir === jobSessionDir) {
        return browser;
    }

    if (browser) {
        try { await browser.close(); } catch (e) { /* ignore */ }
        browser = null;
    }

    sessionDir = jobSessionDir;

    log(`🌐 Launching browser (${isHeadless ? 'headless' : 'visible'})...`);

    browser = await puppeteer.launch({
        headless: isHeadless ? 'new' : false,
        userDataDir: sessionDir,
        defaultViewport: null,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-blink-features=AutomationControlled',
            ...(isHeadless ? [] : ['--start-maximized']),
        ],
    });

    browser.on('targetcreated', async (target) => {
        const page = await target.page().catch(() => null);
        if (page) {
            await page.evaluateOnNewDocument(() => {
                Object.defineProperty(navigator, 'webdriver', { get: () => false });
            }).catch(() => {});
        }
    });

    browser.on('disconnected', () => {
        log('⚠️  Browser disconnected unexpectedly');
        browser = null;
    });

    return browser;
}

// ─── Core: Send Email via New Tab ────────────────────────────

async function sendViaZoho(job, sendMode) {
    const { log_id, session_dir, recipient, subject, body } = job;
    let page = null;

    // Resolve effective mode
    // send_later from API always takes priority (campaign scheduled sends)
    let effectiveMode = sendMode || 'auto';
    if (effectiveMode !== 'send_later') {
        if (cliManual && effectiveMode === 'auto') effectiveMode = 'manual_send';
        if (cliSkipFill) effectiveMode = 'full_manual';
    }

    log(`\n${'═'.repeat(50)}`);
    log(`📧 Job #${log_id}`);
    log(`   To:      ${recipient}`);
    log(`   Subject: ${subject.substring(0, 60)}`);
    log(`   Mode:    ${effectiveMode}`);
    log(`${'─'.repeat(50)}`);

    await pushBotLog('job_picked', `Job #${log_id} → ${recipient}`, log_id, { subject: subject.substring(0, 60), mode: effectiveMode });

    // ─── PRE-SEND VERIFICATION ──────────────────────────
    log('   → Verifying job with server...');
    const verification = await verifyJob(log_id);
    if (!verification.valid) {
        log(`   ⛔ Job invalidated: ${verification.reason}`);
        await pushBotLog('error', `Job #${log_id} invalidated: ${verification.reason}`, log_id);
        return false; // Don't report as failed — just skip
    }
    log('   ✓ Job verified');

    try {
        const b = await getBrowser(session_dir);

        page = await b.newPage();
        await page.setUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
        );
        await page.evaluateOnNewDocument(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => false });
        });

        // ─── Navigate to Zoho Mail ──────────────────────
        log('   → Opening Zoho Mail...');
        await pushBotLog('composing', 'Opening Zoho Mail...', log_id);
        await page.goto('https://mail.zoho.com/zm/', {
            waitUntil: 'networkidle2',
            timeout: 45000,
        });

        const url = page.url();
        if (!url.includes('/zm/') && !url.includes('/zm#')) {
            throw new Error('Session expired — re-login required via dashboard');
        }
        log('   ✓ Logged in');
        await sleep(randomInt(1500, 2500));

        // ─── Click Compose ───────────────────────────────
        log('   → Opening compose...');
        const composeClicked = await page.evaluate(() => {
            const btns = document.querySelectorAll('button, a, [role="button"], span');
            for (const btn of btns) {
                const t = btn.textContent.trim().toLowerCase();
                if (t === 'new mail' || t === 'compose' || t === 'إنشاء' || t === 'بريد جديد') {
                    btn.click();
                    return t;
                }
            }
            for (const sel of ['[data-action="compose"]', '#zmail-compose', 'button[title="Compose"]', 'button[title="New Mail"]']) {
                const el = document.querySelector(sel);
                if (el) { el.click(); return sel; }
            }
            return null;
        });

        if (!composeClicked) {
            log('   → Fallback: navigating to compose URL...');
            await page.goto('https://mail.zoho.com/zm/#compose', {
                waitUntil: 'networkidle2',
                timeout: 30000,
            });
        } else {
            log(`   ✓ Compose via: ${composeClicked}`);
        }

        await sleep(3000);

        // ─── Mode-dependent actions ─────────────────
        if (effectiveMode === 'full_manual') {
            log('   ┌─────────────────────────────────────┐');
            log('   │  🖐️  FULL MANUAL — Fill everything  │');
            log(`   │  To: ${recipient.substring(0, 33).padEnd(33)}│`);
            log('   └─────────────────────────────────────┘');
            await pushBotLog('waiting_user', `Waiting for user to fill & send #${log_id}`, log_id);
            await waitForUserSend(page, log_id);
        } else {
            // Fill fields
            log('   → Filling recipient...');
            if (!await fillToField(page, recipient)) {
                throw new Error('Could not fill the To field');
            }
            log('   ✓ To: ' + recipient);
            await sleep(randomInt(500, 1000));

            log('   → Filling subject...');
            if (!await fillSubjectField(page, subject)) {
                throw new Error('Could not fill the Subject field');
            }
            log('   ✓ Subject filled');
            await sleep(randomInt(500, 1000));

            log('   → Filling body...');
            if (!await fillBodyField(page, body)) {
                throw new Error('Could not fill the Body field');
            }
            log('   ✓ Body filled');
            await sleep(randomInt(1000, 2000));

            // ─── VERIFY FIELDS ARE ACTUALLY FILLED ──────
            log('   → Verifying fields in browser...');
            await pushBotLog('verified', 'Verifying fields are filled correctly', log_id);
            // We verify by checking compose is still open (fields accepted)
            const composeStillOpen = await page.evaluate(() => {
                // Check if compose window/form is still visible
                const sendBtn = document.querySelector('button[data-action="Send"], button[aria-label="Send"]');
                return !!sendBtn;
            }).catch(() => false);

            if (!composeStillOpen) {
                throw new Error('Compose window closed unexpectedly after filling fields');
            }
            log('   ✓ Fields verified');
            await pushBotLog('fields_filled', `Fields filled for #${log_id}`, log_id);

            if (effectiveMode === 'send_later' && job.schedule_send_at) {
                // ─── SEND LATER MODE (Campaign) ─────────
                log('   ┌─────────────────────────────────────┐');
                log('   │  📅  SEND LATER — Scheduling...     │');
                log(`   │     Time: ${job.schedule_send_at}       │`);
                log('   └─────────────────────────────────────┘');
                await pushBotLog('composing', `Scheduling #${log_id} via Send Later for ${job.schedule_send_at}`, log_id);

                const scheduled = await clickSendLater(page, job.schedule_send_at);
                await sleep(randomInt(3000, 5000));

                if (scheduled) {
                    // Verify compose closed
                    const composeClosed = await page.evaluate(() => {
                        const sendBtn = document.querySelector('button[data-action="Send"], button[aria-label="Send"]');
                        return !sendBtn;
                    }).catch(() => true);

                    if (composeClosed) {
                        log(`   ✅ Email #${log_id} scheduled for ${job.schedule_send_at}!`);
                        await reportResult(log_id, 'sent');
                        await pushBotLog('sent', `Email #${log_id} scheduled via Send Later for ${job.schedule_send_at}`, log_id);
                        consecutiveFailures = 0;
                        totalSentThisSession++;
                    } else {
                        log('   ⚠️ Compose still open after schedule attempt');
                        await reportResult(log_id, 'failed', 'Compose did not close after Send Later');
                        await pushBotLog('failed', 'Compose did not close after Send Later', log_id);
                        consecutiveFailures++;
                    }
                } else {
                    log('   ❌ Send Later flow failed — trying manual fallback');
                    await pushBotLog('waiting_user', `Send Later failed, waiting user for #${log_id}`, log_id);
                    await waitForUserSend(page, log_id);
                }
            } else if (effectiveMode === 'manual_send') {
                log('   ┌─────────────────────────────────────┐');
                log('   │  ✋  MANUAL — Review and click Send  │');
                log('   │     Waiting up to 5 minutes...       │');
                log('   └─────────────────────────────────────┘');
                await pushBotLog('waiting_user', `Waiting for user to send #${log_id}`, log_id);
                await waitForUserSend(page, log_id);
            } else {
                // ─── FINAL VERIFY BEFORE AUTO-SEND ──────
                log('   → Final verification before send...');
                const finalCheck = await verifyJob(log_id);
                if (!finalCheck.valid) {
                    log(`   ⛔ Aborted: ${finalCheck.reason}`);
                    await pushBotLog('error', `Auto-send aborted: ${finalCheck.reason}`, log_id);
                    await page.close();
                    return false;
                }

                log('   → Auto-sending...');
                const sendSuccess = await clickSend(page);
                await sleep(randomInt(3000, 5000));

                // Verify compose closed after send
                const composeClosed = await page.evaluate(() => {
                    const sendBtn = document.querySelector('button[data-action="Send"], button[aria-label="Send"]');
                    return !sendBtn;
                }).catch(() => true);

                if (composeClosed) {
                    log(`   ✅ Email #${log_id} sent!`);
                    await reportResult(log_id, 'sent');
                    await pushBotLog('sent', `Email #${log_id} sent to ${recipient}`, log_id);
                    consecutiveFailures = 0;
                    totalSentThisSession++;
                } else {
                    log(`   ⚠️ Compose still open — send may have failed`);
                    await reportResult(log_id, 'failed', 'Compose window did not close after clicking Send');
                    await pushBotLog('failed', 'Compose did not close after send click', log_id);
                    consecutiveFailures++;
                }
            }
        }

        // ─── Close tab ──────────────────────────────────
        await page.close();
        log(`   🔄 Tab closed. Browser stays open.`);
        return true;

    } catch (error) {
        log(`   ❌ Failed: ${error.message}`);
        await reportResult(log_id, 'failed', error.message);
        await pushBotLog('failed', `Job #${log_id} failed: ${error.message}`, log_id);
        consecutiveFailures++;
        if (page) {
            try { await page.close(); } catch (e) { /* ignore */ }
        }
        return false;
    }
}

/**
 * Wait for user to manually click Send.
 */
async function waitForUserSend(page, logId) {
    const waitStart = Date.now();
    const waitMax = 300000; // 5 min

    while (Date.now() - waitStart < waitMax) {
        await sleep(3000);
        try {
            const stillComposing = await page.evaluate(() => {
                const sendBtns = document.querySelectorAll('button[aria-label="Send"], button[data-action="Send"]');
                return sendBtns.length > 0;
            }).catch(() => false);

            if (!stillComposing) {
                log(`   ✅ Email #${logId} — sent by user!`);
                await reportResult(logId, 'sent');
                await pushBotLog('sent', `Email #${logId} sent by user`, logId);
                consecutiveFailures = 0;
                totalSentThisSession++;
                return;
            }

            const url = page.url();
            if (!url.includes('#compose') && url.includes('/zm/')) {
                log(`   ✅ Email #${logId} — compose closed.`);
                await reportResult(logId, 'sent');
                await pushBotLog('sent', `Email #${logId} sent (compose closed)`, logId);
                consecutiveFailures = 0;
                totalSentThisSession++;
                return;
            }
        } catch (e) { /* page might be navigating */ }
    }

    log(`   ⏰ Timeout — not marking as sent.`);
    await reportResult(logId, 'failed', 'User did not send within 5 minutes');
    await pushBotLog('failed', `Timeout waiting for user send #${logId}`, logId);
    consecutiveFailures++;
}

// ─── Field Filling Functions ─────────────────────────────────

async function fillToField(page, recipient) {
    try {
        const toField = await page.evaluateHandle(() => {
            for (const sel of [
                'textarea[name="toAddress"]',
                'input[id="addr_toAddr"]',
                'input[name="to"]',
                'input[placeholder*="To"]',
                'input[aria-label*="To"]',
                'input[aria-label*="إلى"]',
            ]) {
                const el = document.querySelector(sel);
                if (el) return el;
            }
            for (const lbl of document.querySelectorAll('label, span')) {
                const t = lbl.textContent.trim().toLowerCase();
                if (t === 'to' || t === 'إلى') {
                    const p = lbl.closest('div, tr, td');
                    if (p) {
                        const inp = p.querySelector('input, textarea, [contenteditable]');
                        if (inp) return inp;
                    }
                }
            }
            return null;
        });

        if (toField && toField.asElement()) {
            await toField.asElement().click();
            await sleep(300);
            await page.keyboard.down('Control');
            await page.keyboard.press('a');
            await page.keyboard.up('Control');
            await page.keyboard.press('Backspace');
            await sleep(300);
            await page.keyboard.type(recipient, { delay: randomInt(40, 80) });
            await sleep(800);
            await page.keyboard.press('Enter');
            await sleep(500);
            return true;
        }
    } catch (e) { /* next */ }

    try {
        await page.click('body');
        await sleep(300);
        await page.keyboard.press('Tab');
        await sleep(300);
        await page.keyboard.type(recipient, { delay: randomInt(40, 80) });
        await sleep(500);
        await page.keyboard.press('Enter');
        return true;
    } catch (e) { /* failed */ }

    return false;
}

async function fillSubjectField(page, subject) {
    try {
        const field = await page.evaluateHandle(() => {
            for (const sel of [
                'input[name="Subject"]',
                'input[aria-label="Subject"]',
                'input[aria-label*="الموضوع"]',
                'input[placeholder*="Subject"]',
                'input[id="Subject"]',
                'input[id*="subject" i]',
            ]) {
                const el = document.querySelector(sel);
                if (el) return el;
            }
            return null;
        });

        if (field && field.asElement()) {
            await field.asElement().click({ clickCount: 3 });
            await sleep(200);
            await page.keyboard.type(subject, { delay: randomInt(20, 50) });
            return true;
        }
    } catch (e) { /* next */ }

    try {
        return await page.evaluate((subj) => {
            const inp = document.querySelector('input[name="Subject"]') ||
                        document.querySelector('input[aria-label="Subject"]');
            if (inp) {
                inp.focus();
                inp.value = subj;
                inp.dispatchEvent(new Event('input', { bubbles: true }));
                inp.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            }
            return false;
        }, subject);
    } catch (e) { return false; }
}

async function fillBodyField(page, bodyText) {
    const htmlBody = bodyText
        .replace(/\\n/g, '<br>')
        .replace(/\n/g, '<br>')
        .replace(/(<br>){3,}/g, '<br><br>');

    // Strategy 1: Iframe editor
    try {
        const iframes = await page.$$('iframe');
        log(`   → Found ${iframes.length} iframe(s)...`);

        for (const iframe of iframes) {
            try {
                const frame = await iframe.contentFrame();
                if (!frame) continue;

                const isEditor = await frame.evaluate(() => {
                    return document.body && (
                        document.body.isContentEditable ||
                        document.body.getAttribute('contenteditable') === 'true' ||
                        document.designMode === 'on'
                    );
                }).catch(() => false);

                if (isEditor) {
                    await frame.evaluate((html) => {
                        document.body.innerHTML = html;
                        document.body.dispatchEvent(new Event('input', { bubbles: true }));
                    }, htmlBody);
                    log('   → Body via iframe ✓');
                    return true;
                }
            } catch (e) { /* next iframe */ }
        }
    } catch (e) {
        log('   → iframe failed: ' + e.message);
    }

    // Strategy 2: Keyboard
    try {
        log('   → Trying keyboard...');
        await page.keyboard.press('Tab');
        await sleep(500);
        await page.keyboard.press('Tab');
        await sleep(500);
        const plainText = bodyText.replace(/\\n/g, '\n');
        for (const char of plainText) {
            if (char === '\n') {
                await page.keyboard.press('Enter');
            } else {
                await page.keyboard.type(char, { delay: randomInt(5, 15) });
            }
        }
        log('   → Body via keyboard ✓');
        return true;
    } catch (e) {
        log('   → keyboard failed: ' + e.message);
    }

    // Strategy 3: contenteditable
    try {
        return await page.evaluate((html) => {
            const editables = document.querySelectorAll('[contenteditable="true"]');
            for (const el of editables) {
                const rect = el.getBoundingClientRect();
                if (rect.height > 150 && rect.width > 300) {
                    el.focus();
                    el.innerHTML = html;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    return true;
                }
            }
            return false;
        }, htmlBody);
    } catch (e) { return false; }
}

async function clickSend(page) {
    const clicked = await page.evaluate(() => {
        for (const sel of [
            'button[data-action="Send"]',
            'button[aria-label="Send"]',
            'button[aria-label*="إرسال"]',
        ]) {
            const el = document.querySelector(sel);
            if (el) { el.click(); return sel; }
        }
        for (const btn of document.querySelectorAll('button, [role="button"]')) {
            const t = btn.textContent.trim();
            if (t === 'Send' || t === 'إرسال') { btn.click(); return 'text:' + t; }
        }
        return null;
    }).catch(() => null);

    if (clicked) {
        log(`   ✓ Send via: ${clicked}`);
        return true;
    }

    log('   → Using Ctrl+Enter...');
    await page.keyboard.down('Control');
    await page.keyboard.press('Enter');
    await page.keyboard.up('Control');
    return true;
}

/**
 * Click "Send Later" → "Custom Date and Time" → fill date/time → "Schedule and Send"
 * Used for campaign emails to schedule via Zoho's built-in scheduler.
 */
async function clickSendLater(page, scheduleDatetime) {
    // scheduleDatetime format: "2026-04-12 09:30"
    const [datePart, timePart] = scheduleDatetime.split(' ');
    const [year, month, day] = datePart.split('-');
    const formattedDate = `${month}/${day}/${year}`; // Zoho uses MM/DD/YYYY
    const formattedTime = timePart; // HH:MM

    log(`   → Send Later: ${formattedDate} @ ${formattedTime}`);

    // Step 1: Click "Send Later" button
    const sendLaterClicked = await page.evaluate(() => {
        const els = Array.from(document.querySelectorAll('button, a, span, div, li'));
        els.sort((a, b) => a.children.length - b.children.length); // Try leaf nodes first
        
        for (const el of els) {
            const text = el.textContent.trim();
            if ((text === 'Send Later' || text.match(/Send Later/i)) && el.offsetHeight > 0) {
                // Must be a small element (not the whole page wrapper)
                if (el.children.length < 4) {
                    el.click();
                    return true;
                }
            }
        }
        return false;
    }).catch(() => false);

    if (!sendLaterClicked) {
        log('   ✗ Could not find Send Later button');
        return false;
    }

    await sleep(2000); // Wait for modal to pop up

    // Step 2: Click "Custom Date and Time" radio/menu option
    const customClicked = await page.evaluate(() => {
        const els = Array.from(document.querySelectorAll('li, div, span, label, a, button, td'));
        els.sort((a, b) => a.children.length - b.children.length); // Leaf nodes first
        
        for (const el of els) {
            const text = el.textContent?.trim() || '';
            if (text.match(/Custom Date/i) && el.offsetHeight > 0) {
                if (el.children.length < 5) {
                    el.click();
                    return true;
                }
            }
        }
        
        // Try clicking the radio input directly if it exists nearby
        const radios = document.querySelectorAll('input[type="radio"]');
        if (radios.length > 0) {
            radios[radios.length - 1].click();
            return true;
        }
        return false;
    }).catch(() => false);

    if (!customClicked) {
        log('   ✗ Could not find Custom Date and Time option');
        return false;
    }

    await sleep(1000);

    // Step 3 & 4: Keyboard TAB Navigation based on User Sandbox Discovery
    // Zoho breaks fields into [Date] --Tab--> [Hours] --Tab--> [Minutes] --Tab--> [Timezone]
    log(`   → Executing Tab Navigation: Date(${formattedDate}), Time(${formattedTime})`);

    // Ensure focus starts on the "Custom Date" radio button
    await page.evaluate(() => {
        const radios = Array.from(document.querySelectorAll('input[type="radio"]'));
        if (radios.length > 0) {
            radios[radios.length - 1].focus();
        }
    });
    
    await sleep(400);

    const [hoursStr, minutesStr] = formattedTime.split(':');

    // Calculate diffDays mathematically
    let diffDays = 0;
    try {
        const today = new Date();
        today.setHours(0,0,0,0);
        const targetDateObj = new Date(year, parseInt(month, 10) - 1, day);
        targetDateObj.setHours(0,0,0,0);
        
        diffDays = Math.round((targetDateObj - today) / 86400000);
        if (diffDays < 0) diffDays = 0;
    } catch (e) {}

    // Step 3 & 4: Keyboard TAB Navigation based on User Sandbox Discovery
    log(`   → Keyboard Sequence: Date(+${diffDays} days), Time(${hoursStr}:${minutesStr})`);

    // Ensure focus starts on the "Custom Date" radio button
    await page.evaluate(() => {
        const radios = Array.from(document.querySelectorAll('input[type="radio"]'));
        if (radios.length > 0) {
            radios[radios.length - 1].focus();
        }
    });
    
    await sleep(400);

    // 1. Tab to Date Field
    await page.keyboard.press('Tab');
    await sleep(400);
    
    // Open Calendar
    await page.keyboard.press('Space');
    await sleep(600); // Wait for calendar animation
    
    // Move forward X days using Left/Right arrows
    for (let i = 0; i < diffDays; i++) {
        await page.keyboard.press('ArrowRight');
        await sleep(150);
    }
    
    // Confirm Date selection
    await page.keyboard.press('Space');
    await sleep(500);

    // 2. Tab to Hours Field
    await page.keyboard.press('Tab');
    await sleep(300);
    // Directly type hours (No Ctrl+A to avoid breaking component state)
    await page.keyboard.type(hoursStr, { delay: 150 });
    await sleep(300);

    // 3. Tab to Minutes Field
    await page.keyboard.press('Tab');
    await sleep(300);
    await page.keyboard.type(minutesStr, { delay: 150 });
    await sleep(300);

    // 4. Tab to Timezone
    await page.keyboard.press('Tab');
    await sleep(300);
    
    log(`   ✓ Form filled via Tab navigation!`);

    await sleep(500);

    // Step 5: Click "Schedule and Send"
    const scheduleClicked = await page.evaluate(() => {
        for (const el of document.querySelectorAll('button, [role="button"], a')) {
            const text = el.textContent.trim();
            if (text.includes('Schedule') && text.includes('Send')) {
                el.click();
                return true;
            }
        }
        return false;
    }).catch(() => false);

    if (scheduleClicked) {
        log('   ✓ Scheduled via Send Later!');
        return true;
    }

    log('   ✗ Could not click Schedule and Send');
    return false;
}

// ─── Utilities ───────────────────────────────────────────────

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

function log(msg) {
    const time = new Date().toLocaleTimeString('en-GB', { hour12: false });
    console.log(`[${time}] ${msg}`);
}

// ─── Graceful Shutdown ───────────────────────────────────────

async function shutdown() {
    log('\n🛑 Shutting down...');
    await pushBotLog('stopped', 'Bot shutting down gracefully');
    if (browser) {
        try { await browser.close(); } catch (e) { /* ignore */ }
    }
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

// ─── Main ────────────────────────────────────────────────────

async function main() {
    console.log('');
    console.log('╔════════════════════════════════════════╗');
    console.log(`║   🔥 ColdForge ${cliSendLater ? 'Campaign' : 'Warming'} Bot v4.1    ║`);
    console.log('║   Safety-First Architecture            ║');
    console.log('╚════════════════════════════════════════╝');
    console.log(`  Mode:     ${isLoop ? '♻️  Continuous loop' : '▶️  Single run'}`);
    console.log(`  Browser:  ${isHeadless ? '👻 Headless' : '👁️  Visible'}`);
    console.log(`  Send:     ${cliSendLater ? '📅 Send Later (Zoho schedule)' : cliManual ? '✋ Manual (you click Send)' : '🤖 Auto-send'}`);
    console.log(`  Account:  ${accountId ? '🔒 #' + accountId + ' only' : '🌐 All accounts'}`);
    if (cliTimezone) console.log(`  Timezone: 🌍 ${cliTimezone}`);
    console.log(`  API:      ${LARAVEL_URL}`);
    console.log(`  Session:  ${SESSION_ID}`);
    console.log(`  Limits:   ${MAX_CONSECUTIVE_FAILURES} max failures, ${MAX_PER_SESSION} max/session`);
    console.log('');

    await pushBotLog('started', `Bot v4.1 started — Mode: ${isLoop ? 'loop' : 'single'}, Send: ${cliSendLater ? 'send_later' : cliManual ? 'manual' : 'auto'}`);

    do {
        // ─── Safety checks ──────────────────────────
        if (consecutiveFailures >= MAX_CONSECUTIVE_FAILURES) {
            log(`\n🚨 EMERGENCY STOP: ${consecutiveFailures} consecutive failures!`);
            log('   The bot has stopped to prevent damage.');
            log('   Fix the issue and restart the bot.');
            await pushBotLog('stopped', `Emergency stop: ${consecutiveFailures} consecutive failures`);
            break;
        }

        if (totalSentThisSession >= MAX_PER_SESSION) {
            log(`\n🛑 SESSION LIMIT: Sent ${totalSentThisSession} emails this session.`);
            log('   Restart the bot for a new session.');
            await pushBotLog('stopped', `Session limit reached: ${totalSentThisSession} emails sent`);
            break;
        }

        const jobData = await getNextJob();

        if (jobData.has_job) {
            await sendViaZoho(jobData.job, jobData.send_mode);

            // Send Later = just scheduling, so shorter delay. Regular = actual send, longer delay.
            const isSendLater = jobData.send_mode === 'send_later';
            const delay = isSendLater
                ? randomInt(15, 30)   // Just scheduling — quick turnaround
                : (jobData.suggested_delay || randomInt(120, 300)); // Actual send — longer gap
            log(`   ⏳ Waiting ${delay}s before next...`);
            await pushBotLog('idle', `Waiting ${delay}s before next job`);
            await sleep(delay * 1000);
        } else {
            // Check if all jobs for this account are done
            if (jobData.queue_empty && accountId) {
                log(`\n🎉 ════════════════════════════════════════`);
                log(`   All jobs for account #${accountId} completed!`);
                log(`   Sent ${totalSentThisSession} emails this session.`);
                log(`   ════════════════════════════════════════`);
                await pushBotLog('stopped', `Daily round complete: ${totalSentThisSession} emails sent for account #${accountId}`);
                break;
            }

            if (isLoop) {
                log(`💤 No jobs. Reason: ${jobData.reason || 'Queue empty'}`);
                log(`   Next check in ${Math.round(intervalSeconds / 60)} min...`);
                await pushBotLog('idle', `No jobs: ${jobData.reason || 'Queue empty'}. Polling in ${Math.round(intervalSeconds / 60)}m`);
                await sleep(intervalSeconds * 1000);
            }
        }
    } while (isLoop);

    log('\n✅ Bot finished.');
    await pushBotLog('stopped', 'Bot finished normally');
    
    // Notify server that this account's campaign work is done
    if (accountId && cliSendLater) {
        try {
            await axios.post(`${LARAVEL_URL}/api/warming/bot-complete`, {
                account_id: accountId,
                session_id: SESSION_ID,
            });
            log('   📡 Server notified of completion.');
        } catch (e) { /* silent */ }
    }
    
    if (browser) {
        try { await browser.close(); } catch (e) { /* ignore */ }
    }
    
    process.exit(0);
}

main().catch(async (err) => {
    console.error('Fatal:', err);
    await pushBotLog('error', `Fatal crash: ${err.message}`);
    process.exit(1);
});
