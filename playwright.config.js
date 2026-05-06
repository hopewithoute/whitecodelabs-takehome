import { defineConfig, devices } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';

const chromiumExecutablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH || '/usr/bin/chromium-browser';
const baseURL = testBaseUrl();
const serverUrl = new URL(baseURL);
const serverHost = serverUrl.hostname || '127.0.0.1';
const serverPort = serverUrl.port || '8000';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30000,
    expect: {
        timeout: 5000,
    },
    fullyParallel: false,
    workers: 1,
    reporter: 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    webServer: {
        command: `php artisan serve --host=${serverHost} --port=${serverPort}`,
        reuseExistingServer: !process.env.CI,
        timeout: 30000,
        url: baseURL,
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                launchOptions: {
                    executablePath: chromiumExecutablePath,
                },
            },
        },
    ],
});

function testBaseUrl() {
    const rawUrl = process.env.PLAYWRIGHT_BASE_URL
        || process.env.APP_URL
        || envFileValue('APP_URL')
        || 'http://127.0.0.1:8000';
    const url = new URL(rawUrl.replace(/^http:\/\/localhost$/, 'http://127.0.0.1'));

    if (!url.port && ['127.0.0.1', 'localhost'].includes(url.hostname)) {
        url.port = process.env.APP_PORT || envFileValue('APP_PORT') || '8000';
    }

    return url.toString().replace(/\/$/, '');
}

function envFileValue(key) {
    if (!existsSync('.env')) {
        return null;
    }

    const line = readFileSync('.env', 'utf8')
        .split('\n')
        .find((entry) => entry.startsWith(`${key}=`));

    return line?.slice(key.length + 1).trim().replace(/^"|"$/g, '') || null;
}
