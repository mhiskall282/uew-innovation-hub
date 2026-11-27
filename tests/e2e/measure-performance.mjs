// Lab measurement of load performance (not field data). Usage:
//   node tests/e2e/measure-performance.mjs http://127.0.0.1:8000
// Reports TTFB, LCP, CLS, request count and transfer size per page, on a cold
// cache, with optional "Fast 3G"-like throttling (THROTTLE=1).
import { chromium } from '@playwright/test';

const base = process.argv[2] ?? 'http://127.0.0.1:8000';
const paths = ['/', '/programmes', '/projects', '/news', '/community', '/sign-in'];
const browser = await chromium.launch({ channel: process.env.E2E_CHANNEL || undefined });

const rows = [];
for (const path of paths) {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
    const page = await context.newPage();
    if (process.env.THROTTLE) {
        const cdp = await context.newCDPSession(page);
        await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: (1.6 * 1024 * 1024) / 8, uploadThroughput: (750 * 1024) / 8 });
        await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    }
    let bytes = 0;
    let requests = 0;
    page.on('response', async (r) => {
        requests++;
        const len = Number(r.headers()['content-length'] ?? 0);
        bytes += len || (await r.body().catch(() => Buffer.alloc(0))).length;
    });
    await page.addInitScript(() => {
        window.__lcp = 0;
        window.__cls = 0;
        new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp = e.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true });
        new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: 'layout-shift', buffered: true });
    });
    await page.goto(base + path, { waitUntil: 'load' });
    await page.waitForTimeout(1500);
    const m = await page.evaluate(() => {
        const nav = performance.getEntriesByType('navigation')[0];
        return { ttfb: nav.responseStart, load: nav.loadEventEnd, lcp: window.__lcp, cls: window.__cls };
    });
    rows.push({ path, ttfb: Math.round(m.ttfb), lcp: Math.round(m.lcp), load: Math.round(m.load), cls: Number(m.cls.toFixed(3)), requests, kb: Math.round(bytes / 1024) });
    await context.close();
}
await browser.close();
console.table(rows);
