// Diagnostic: lists pages wider than the viewport and the elements that stick out.
// Usage: node tests/e2e/find-overflow.mjs http://127.0.0.1:8000 320
import { chromium } from '@playwright/test';

const [base = 'http://127.0.0.1:8000', width = '320'] = process.argv.slice(2);
const pages = {
    guest: ['/', '/programmes', '/programmes/agritech-seed-grant-climate-smart-farming', '/projects', '/news', '/community', '/contact', '/sign-up'],
    'member@uew.edu.gh': ['/portal', '/portal/applications', '/portal/reports', '/notifications'],
    'admin@uew.edu.gh': ['/workspace', '/workspace/reports', '/workspace/progress-reports', '/workspace/applications'],
};

const browser = await chromium.launch({ channel: process.env.E2E_CHANNEL || undefined });
for (const [who, paths] of Object.entries(pages)) {
    const context = await browser.newContext({ viewport: { width: Number(width), height: 900 } });
    const page = await context.newPage();
    if (who !== 'guest') {
        await page.goto(`${base}/sign-in`);
        await page.fill('#email', who);
        await page.fill('#password', 'password');
        await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
    }
    for (const path of paths) {
        await page.goto(base + path);
        const result = await page.evaluate(() => {
            const vw = document.documentElement.clientWidth;
            const wide = [];
            for (const el of document.querySelectorAll('body *')) {
                const r = el.getBoundingClientRect();
                if (r.width && r.right > vw + 1 && getComputedStyle(el).position !== 'fixed') {
                    // Report the outermost offender only.
                    if (!wide.some((w) => w.el.contains(el))) wide.push({ el, right: Math.round(r.right) });
                }
            }
            return {
                scroll: document.documentElement.scrollWidth,
                vw,
                offenders: wide.slice(0, 5).map((w) => `${w.el.tagName.toLowerCase()}.${String(w.el.className).trim().split(/\s+/).slice(0, 6).join('.')} → ${w.right}px`),
            };
        });
        if (result.scroll > result.vw + 1) console.log(`${who} ${path}: ${result.scroll}px > ${result.vw}px\n  ${result.offenders.join('\n  ')}`);
    }
    await context.close();
}
await browser.close();
