import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

/**
 * Browser tests (end-to-end, accessibility, responsive).
 *
 * Runs against an isolated SQLite database (database/e2e.sqlite) rebuilt with
 * synthetic demo data on every run; it never touches the dev or live database.
 * PHP's built-in server is used so the environment below reaches the app.
 *
 *   npm run test:e2e
 */
const port = Number(process.env.E2E_PORT ?? 8010);
const db = path.resolve('database/e2e.sqlite');
const channel = process.env.E2E_CHANNEL || undefined;

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: false,
    workers: 1, // PHP's built-in server handles one request at a time
    retries: process.env.CI ? 1 : 0,
    timeout: 60_000,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : [['list']],
    use: {
        baseURL: `http://127.0.0.1:${port}`,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    // E2E_CHANNEL=msedge runs the Chromium projects in the locally installed
    // Microsoft Edge instead of a downloaded browser. CI installs all engines.
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'], channel } },
        { name: 'firefox', use: { ...devices['Desktop Firefox'] }, testIgnore: /responsive/ },
        { name: 'webkit', use: { ...devices['Desktop Safari'] }, testIgnore: /responsive/ },
        { name: 'mobile-android', use: { ...devices['Pixel 7'], channel }, testMatch: /journeys/ },
        { name: 'mobile-ios', use: { ...devices['iPhone 14'] }, testMatch: /journeys/ },
    ],
    webServer: {
        command: `node tests/e2e/serve.mjs`,
        url: `http://127.0.0.1:${port}/up`,
        timeout: 180_000,
        reuseExistingServer: false,
        env: {
            E2E_PORT: String(port),
            APP_ENV: 'local',
            APP_DEBUG: 'false',
            APP_URL: `http://127.0.0.1:${port}`,
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: db,
            DB_URL: '',
            CACHE_STORE: 'array',
            SESSION_DRIVER: 'file',
            QUEUE_CONNECTION: 'sync',
            MAIL_MAILER: 'array',
            SEED_PASSWORD: 'password',
            SEARCH_INDEXING: 'false',
        },
    },
});
