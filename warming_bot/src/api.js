const axios = require('axios');
const { log, error } = require('./logger');

const LARAVEL_URL = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
// Make Session ID globally accessible across modules
const SESSION_ID = `bot_${Date.now()}_${Math.random().toString(36).substring(2, 8)}`;

async function getNextJob(accountId, cliSendLater) {
    try {
        const params = accountId ? { account_id: accountId } : {};
        if (cliSendLater) params.mode = 'send_later';
        log(`API Request: Fetching next job from ${LARAVEL_URL}...`);
        const { data } = await axios.get(`${LARAVEL_URL}/api/warming/next-job`, { params });
        return data;
    } catch (err) {
        error('Laravel API unreachable when fetching job', err, true);
        return { has_job: false, reason: 'API unreachable' };
    }
}

async function reportResult(logId, status, errorMessage = null) {
    try {
        log(`API Request: Reporting result for job #${logId} (Status: ${status})`);
        await axios.post(`${LARAVEL_URL}/api/warming/report`, {
            log_id: logId,
            status,
            error_message: errorMessage,
        });
    } catch (err) {
        error(`Failed to report result for job #${logId}`, err);
    }
}

async function verifyJob(logId) {
    try {
        log(`API Request: Verifying job #${logId}...`);
        const { data } = await axios.get(`${LARAVEL_URL}/api/warming/verify-job/${logId}`);
        return data;
    } catch (err) {
        error(`Job verify failed for #${logId}`, err);
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
        // We only use logger.error if we need to see it in development
        error('Failed to push bot log', err, false);
    }
}

module.exports = {
    SESSION_ID,
    getNextJob,
    reportResult,
    verifyJob,
    pushBotLog
};
