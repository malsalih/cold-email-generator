const { log, error } = require('./logger');
const { sleep, randomInt } = require('./utils');
const api = require('./api');
const browserManager = require('./browser');

/**
 * Handle mode-dependent manual send loop.
 */
async function waitForUserSend(page, logId) {
    let waitLoops = 0;
    while (waitLoops < 60) { // 5 minutes max (60 * 5s)
        await sleep(5000);
        waitLoops++;
        try {
            const url = page.url();
            if (!url.includes('#compose')) {
                log(`   ✓ Sent detected (URL changed)`);
                return;
            }
            const composeBox = await page.$('.ZM-C'); // Zoho Compose block
            if (!composeBox) {
                log(`   ✓ Sent detected (Compose closed)`);
                return;
            }
        } catch (e) { /* page might be navigating */ }
    }

    log(`   ⏰ Timeout — not marking as sent.`);
    await api.reportResult(logId, 'failed', 'User did not send within 5 minutes');
    await api.pushBotLog('failed', `Timeout waiting for user send #${logId}`, logId);
    throw new Error('User timeout');
}

/**
 * Click "Send Later" → "Custom Date and Time" → fill date/time → "Schedule and Send"
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

    await sleep(2000);

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

    // Calculate diffDays mathematically
    let diffDays = 0;
    try {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const targetDateObj = new Date(year, parseInt(month, 10) - 1, day);
        targetDateObj.setHours(0, 0, 0, 0);

        diffDays = Math.round((targetDateObj - today) / 86400000);
        if (diffDays < 0) diffDays = 0;
    } catch (e) { }

    const [hoursStr, minutesStr] = formattedTime.split(':');
    log(`   → Keyboard Sequence: Date(+${diffDays} days), Time(${hoursStr}:${minutesStr})`);

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

    await page.keyboard.press('Space');
    await sleep(600);

    for (let i = 0; i < diffDays; i++) {
        await page.keyboard.press('ArrowRight');
        await sleep(150);
    }

    await page.keyboard.press('Space');
    await sleep(500);

    // 2. Tab to Hours
    await page.keyboard.press('Tab');
    await sleep(300);
    await page.keyboard.type(hoursStr, { delay: 150 });
    await sleep(300);

    // 3. Tab to Minutes
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

// ---- FIELD FILLING ----
async function fillToField(page, recipient) {
    try {
        const toField = await page.evaluateHandle(() => {
            for (const sel of ['textarea[name="toAddress"]', 'input[id="addr_toAddr"]', 'input[name="to"]', 'input[placeholder*="To"]', 'input[aria-label*="To"]', 'input[aria-label*="إلى"]']) {
                const el = document.querySelector(sel);
                if (el) return el;
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

    return false;
}

async function fillSubjectField(page, subject) {
    try {
        const field = await page.evaluateHandle(() => {
            for (const sel of ['input[name="Subject"]', 'input[aria-label="Subject"]', 'input[aria-label*="الموضوع"]', 'input[placeholder*="Subject"]', 'input[id="Subject"]', 'input[id*="subject" i]']) {
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
    } catch (e) { return false; }
}

async function fillBodyField(page, bodyText) {
    const htmlBody = bodyText
        .replace(/\\n/g, '<br>')
        .replace(/\n/g, '<br>')
        .replace(/(<br>){3,}/g, '<br><br>');

    try {
        const iframes = await page.$$('iframe');
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
                        document.body.innerHTML = html + '<br><br>' + document.body.innerHTML;
                        document.body.dispatchEvent(new Event('input', { bubbles: true }));
                    }, htmlBody);
                    return true;
                }
            } catch (e) { }
        }
    } catch (e) { }

    // Keyboard fallback
    try {
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
        return true;
    } catch (e) { }

    return false;
}

async function clickSend(page) {
    const clicked = await page.evaluate(() => {
        for (const sel of ['button[data-action="Send"]', 'button[aria-label="Send"]', 'button[aria-label*="إرسال"]']) {
            const el = document.querySelector(sel);
            if (el) { el.click(); return true; }
        }
        return false;
    }).catch(() => false);

    if (clicked) return true;

    await page.keyboard.down('Control');
    await page.keyboard.press('Enter');
    await page.keyboard.up('Control');
    return true;
}

/**
 * Handle the Zoho Mail send operation
 */
async function sendViaZoho(job, effectiveMode, cliManual, cliSkipFill, isHeadless) {
    const { log_id, session_dir, recipient, subject, body } = job;
    let page = null;

    log(`\n${'═'.repeat(50)}`);
    log(`📧 Job #${log_id}`);
    log(`   To:      ${recipient}`);
    log(`   Subject: ${subject.substring(0, 60)}`);
    log(`   Mode:    ${effectiveMode}`);
    log(`${'─'.repeat(50)}`);

    await api.pushBotLog('job_picked', `Job #${log_id} → ${recipient}`, log_id, { subject: subject.substring(0, 60), mode: effectiveMode });

    log('   → Verifying job with server...');
    const verification = await api.verifyJob(log_id);
    if (!verification.valid) {
        log(`   ⛔ Job invalidated: ${verification.reason}`);
        await api.pushBotLog('error', `Job #${log_id} invalidated: ${verification.reason}`, log_id);
        return false; // Skip
    }
    log('   ✓ Job verified');

    try {
        const b = await browserManager.getBrowser(session_dir, isHeadless);

        page = await b.newPage();
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');

        log('   → Opening Zoho Mail...');
        await api.pushBotLog('composing', 'Opening Zoho Mail...', log_id);
        await page.goto('https://mail.zoho.com/zm/', { waitUntil: 'networkidle2', timeout: 45000 });

        if (!page.url().includes('/zm')) throw new Error('Session expired — re-login required');

        log('   ✓ Logged in');
        await sleep(randomInt(1500, 2500));

        if (job.is_followup) {
            log('   ┌─────────────────────────────────────┐');
            log('   │  🔗  FOLLOW-UP MODE (TRUE REPLY)    │');
            log('   └─────────────────────────────────────┘');
            await page.goto('https://mail.zoho.com/zm/#mail/folder/sent', { waitUntil: 'networkidle2' });
            await sleep(3000);

            log('   → Searching for recipient and original subject...');
            let searchSuccess = false;
            try {
                const searchInputSel = '#SQZMMD_Search, input.mswBox, input[placeholder*="Search"]';
                await page.waitForSelector(searchInputSel, { timeout: 10000 });
                await page.click(searchInputSel);
                await sleep(500);

                // Clear input first
                await page.keyboard.down('Control');
                await page.keyboard.press('a');
                await page.keyboard.up('Control');
                await page.keyboard.press('Backspace');
                await sleep(300);

                // Type the "to" filter
                await page.keyboard.type('to:' + recipient, { delay: 50 });
                await sleep(300);
                await page.keyboard.press('Enter');
                await sleep(800);

                if (job.original_subject) {
                    await page.keyboard.type('subject:' + job.original_subject, { delay: 50 });
                    await sleep(300);
                    await page.keyboard.press('Enter');
                }

                searchSuccess = true;
            } catch (e) {
                searchSuccess = false;
            }

            if (!searchSuccess) {
                let fallbackHash = `to:${recipient}`;
                if (job.original_subject) fallbackHash += ` subject:"${job.original_subject}"`;
                await page.goto(`https://mail.zoho.com/zm/#search/${encodeURIComponent(fallbackHash)}`, { waitUntil: 'networkidle2' });
            }

            await sleep(6000);

            log('   → Opening original email...');
            const opened = await page.evaluate(() => {
                const emails = document.querySelectorAll('.zmRow, .mlRow, [data-url_id], .zmLIRow');
                if (emails && emails.length > 0) { emails[0].click(); return true; }
                return false;
            });

            if (!opened) {
                log('   ⚠️ Could not find original email in search. Falling back to new compose...');
                job.is_followup = false; // Override internally to trigger compose field logic later
                await page.goto('https://mail.zoho.com/zm/#compose', { waitUntil: 'networkidle2', timeout: 30000 });
                await sleep(3000);
            } else {
                await sleep(4000);

                log('   → Clicking Reply...');
                const replyClicked = await page.evaluate(() => {
                    for (const sel of ['[data-do="reply"]', '[data-action="reply"]', 'span[title="Reply"]', 'button[title="Reply"]']) {
                        const el = document.querySelector(sel);
                        if (el) { el.click(); return true; }
                    }
                    const btns = document.querySelectorAll('button, a, span, div');
                    for (let b of btns) {
                        const txt = b.textContent.trim().toLowerCase();
                        if (txt === 'reply' || txt === 'رد') { b.click(); return true; }
                    }
                    return false;
                });

                if (!replyClicked) throw new Error("Could not find 'Reply' button");
                await sleep(3000);
            }
        }

        if (!job.is_followup && !page.url().includes('#compose')) {
            log('   → Opening compose...');
            const composeClicked = await page.evaluate(() => {
                const btns = document.querySelectorAll('button, a, [role="button"], span');
                for (const btn of btns) {
                    const t = btn.textContent.trim().toLowerCase();
                    if (t === 'new mail' || t === 'compose' || t === 'إنشاء' || t === 'بريد جديد') {
                        btn.click(); return true;
                    }
                }
                for (const sel of ['[data-action="compose"]', '#zmail-compose', 'button[title="Compose"]']) {
                    const el = document.querySelector(sel);
                    if (el) { el.click(); return true; }
                }
                return false;
            });

            if (!composeClicked) await page.goto('https://mail.zoho.com/zm/#compose', { waitUntil: 'networkidle2', timeout: 30000 });
            await sleep(3000);
        }

        if (effectiveMode === 'full_manual') {
            await waitForUserSend(page, log_id);
        } else {
            if (!job.is_followup) {
                log('   → Filling recipient...');
                if (!await fillToField(page, recipient)) throw new Error('Could not fill the To field');
                await sleep(randomInt(500, 1000));
                log('   → Filling subject...');
                if (!await fillSubjectField(page, subject)) throw new Error('Could not fill the Subject field');
                await sleep(randomInt(500, 1000));
            } else {
                log('   ✓ To and Subject pre-filled by Reply action.');
                await sleep(1000);
            }

            log('   → Filling body...');
            if (!await fillBodyField(page, body)) throw new Error('Could not fill the Body field');
            await sleep(randomInt(1000, 2000));

            const composeStillOpen = await page.evaluate(() => !!document.querySelector('button[data-action="Send"], button[aria-label="Send"]')).catch(() => false);
            if (!composeStillOpen) throw new Error('Compose window closed unexpectedly');

            if (effectiveMode === 'send_later' && job.schedule_send_at) {
                log('   ┌─────────────────────────────────────┐');
                log('   │  📅  SEND LATER — Scheduling...     │');
                log('   └─────────────────────────────────────┘');
                await api.pushBotLog('composing', `Scheduling #${log_id} via Send Later`, log_id);

                const scheduled = await clickSendLater(page, job.schedule_send_at);
                await sleep(randomInt(3000, 5000));

                if (scheduled) {
                    await api.reportResult(log_id, 'sent');
                    await api.pushBotLog('sent', `Job #${log_id} scheduled via Send Later`, log_id);
                } else {
                    throw new Error('Failed to click Send Later sequence');
                }
            } else if (effectiveMode === 'auto') {
                log('   → Sending...');
                await clickSend(page);
                await sleep(randomInt(3000, 4000));
                await api.reportResult(log_id, 'sent');
                await api.pushBotLog('sent', `Job #${log_id} auto-sent successfully`, log_id);
            } else if (effectiveMode === 'manual_send') {
                await api.pushBotLog('waiting_user', 'Waiting for user to click SEND', log_id);
                await waitForUserSend(page, log_id);
            }
        }

        await page.close();
        return true;

    } catch (err) {
        error(`Failed to send job #${log_id}`, err);
        await api.reportResult(log_id, 'failed', err.message);
        await api.pushBotLog('failed', `Error: ${err.message}`, log_id);

        if (page) try { await page.close(); } catch (e) { }
        throw err;
    }
}

module.exports = {
    sendViaZoho
};
