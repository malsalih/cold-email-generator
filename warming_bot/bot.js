/**
 * ColdForge Warming Bot v4.2 — Modular Architecture
 * 
 * Usage:
 *   node index.js                       # Visible browser, process all pending
 *   node index.js --loop                # Continuous loop
 *   node index.js --headless            # Headless mode
 *   node index.js --account=1           # Process only account 1
 */

const { log, error, isDev } = require('./src/logger');
const { sleep, randomInt } = require('./src/utils');
const api = require('./src/api');
const browserManager = require('./src/browser');
const zohoMail = require('./src/zohoMail');

// Parse Arguments
const args = {};
process.argv.slice(2).forEach(arg => {
    const match = arg.match(/^--([^=]+)(?:=(.*))?$/);
    if (match) args[match[1]] = match[2] !== undefined ? match[2] : true;
});

const isLoop = !!args.loop;
const isHeadless = !!args.headless;
const cliManual = !!args.manual;
const cliSkipFill = !!args['skip-fill'];
const cliSendLater = !!args['send-later'];
const cliTimezone = args.timezone || null;
const intervalSeconds = parseInt(args.interval) || 120;
const accountId = args.account || null;

// Limits
let consecutiveFailures = 0;
const MAX_CONSECUTIVE_FAILURES = 5;
let totalSentThisSession = 0;
const MAX_PER_SESSION = 50;

async function shutdown() {
    log('\n🛑 Shutting down...', true);
    await api.pushBotLog('stopped', 'Bot shutting down gracefully');
    await browserManager.closeBrowser();
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

async function main() {
    console.log('');
    console.log('╔════════════════════════════════════════╗');
    console.log(`║   🔥 ColdForge ${cliSendLater ? 'Campaign' : 'Warming'} Bot v4.2    ║`);
    console.log('║   Modular & Secure Architecture        ║');
    console.log('╚════════════════════════════════════════╝');
    console.log(`  Mode:     ${isLoop ? '♻️  Continuous loop' : '▶️  Single run'}`);
    console.log(`  Browser:  ${isHeadless ? '👻 Headless' : '👁️  Visible'}`);
    console.log(`  Send:     ${cliSendLater ? '📅 Send Later' : cliManual ? '✋ Manual' : '🤖 Auto-send'}`);
    console.log(`  Target:   ${accountId ? '🔒 Account #' + accountId : '🌐 All accounts'}`);
    if (isDev) console.log(`  Env:      🛠️  Development (Verbose Logging)`);
    console.log('');

    await api.pushBotLog('started', `Bot v4.2 started — Mode: ${isLoop ? 'loop' : 'single'}`);

    do {
        if (consecutiveFailures >= MAX_CONSECUTIVE_FAILURES) {
            error(`EMERGENCY STOP: ${consecutiveFailures} consecutive failures!`, null, true);
            await api.pushBotLog('stopped', `Emergency stop: ${consecutiveFailures} consecutive failures`);
            break;
        }

        if (totalSentThisSession >= MAX_PER_SESSION) {
            log(`\n🛑 SESSION LIMIT: Sent ${totalSentThisSession} emails this session.`, true);
            await api.pushBotLog('stopped', `Session limit reached`);
            break;
        }

        const jobData = await api.getNextJob(accountId, cliSendLater);

        if (jobData.has_job) {
            try {
                const success = await zohoMail.sendViaZoho(jobData.job, jobData.send_mode, cliManual, cliSkipFill, isHeadless);
                
                if (success) {
                    consecutiveFailures = 0;
                    totalSentThisSession++;
                }

                const isSendLater = jobData.send_mode === 'send_later';
                const delay = isSendLater ? randomInt(15, 30) : (jobData.suggested_delay || randomInt(120, 300));
                
                log(`   ⏳ Waiting ${delay}s before next...`, true);
                await api.pushBotLog('idle', `Waiting ${delay}s before next job`);
                await sleep(delay * 1000);
            } catch (err) {
                consecutiveFailures++;
                log(`   ⚠️ Failure recorded (${consecutiveFailures}/${MAX_CONSECUTIVE_FAILURES})`, true);
                await sleep(10000); // Backoff on failure
            }
        } else {
            if (jobData.queue_empty && accountId) {
                log(`\n🎉 All jobs for account #${accountId} completed!`, true);
                await api.pushBotLog('stopped', `Daily round complete for account #${accountId}`);
                break;
            }

            if (isLoop) {
                log(`💤 No jobs. Reason: ${jobData.reason || 'Queue empty'}`, true);
                log(`   Next check in ${Math.round(intervalSeconds / 60)} min...`, true);
                await sleep(intervalSeconds * 1000);
            }
        }
    } while (isLoop);

    log('\n✅ Bot finished.', true);
    await api.pushBotLog('stopped', 'Bot finished normally');
    
    // Notify server of completion
    if (accountId && cliSendLater) {
        const axios = require('axios');
        try {
            await axios.post(`${process.env.LARAVEL_URL || 'http://127.0.0.1:8000'}/api/warming/bot-complete`, {
                account_id: accountId,
                session_id: api.SESSION_ID,
            });
        } catch (e) { /* silent */ }
    }
    
    await browserManager.closeBrowser();
    process.exit(0);
}

main().catch(async (err) => {
    error('Fatal crash', err, true);
    await api.pushBotLog('error', `Fatal crash: ${err.message}`);
    process.exit(1);
});
