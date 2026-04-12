const isDev = process.env.NODE_ENV !== 'production';

function log(message, force = false) {
    if (isDev || force) {
        const timestamp = new Date().toLocaleTimeString();
        console.log(`[${timestamp}] ${message}`);
    }
}

function error(message, err = null, force = true) {
    if (isDev || force) {
        const timestamp = new Date().toLocaleTimeString();
        console.error(`[${timestamp}] ❌ ERROR: ${message}`);
        if (err && isDev) {
            console.error(err);
        }
    }
}

module.exports = { log, error, isDev };
