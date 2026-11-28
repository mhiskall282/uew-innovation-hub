// Parses every ```mermaid block in docs/ with the real Mermaid parser in a headless browser.
// Run: node scripts/mermaid-check.mjs   (set E2E_CHANNEL=msedge to use the installed Edge)
import { chromium } from '@playwright/test';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const root = join(import.meta.dirname, '..');
const files = [];
const walk = (dir) => readdirSync(dir).forEach((n) => {
    const p = join(dir, n);
    if (statSync(p).isDirectory()) walk(p);
    else if (p.endsWith('.md')) files.push(p);
});
walk(join(root, 'docs'));

const blocks = [];
for (const f of files) {
    const text = readFileSync(f, 'utf8').replace(/\r\n/g, '\n');
    const re = /```mermaid\s*\n([\s\S]*?)```/g;
    let m, i = 0;
    while ((m = re.exec(text))) blocks.push({ file: relative(root, f), index: ++i, code: m[1] });
}

const browser = await chromium.launch({ channel: process.env.E2E_CHANNEL || undefined });
const page = await browser.newPage();
await page.setContent('<html><body></body></html>');
await page.addScriptTag({ url: 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js' });
const results = await page.evaluate(async (blocks) => {
    window.mermaid.initialize({ startOnLoad: false });
    const out = [];
    for (const b of blocks) {
        try {
            await window.mermaid.parse(b.code);
            out.push({ ok: true });
        } catch (e) {
            out.push({ ok: false, error: String(e.message || e).split('\n').slice(0, 4).join(' | ') });
        }
    }
    return out;
}, blocks);
await browser.close();

let failed = 0;
results.forEach((r, i) => {
    if (!r.ok) {
        failed++;
        console.log(`FAIL ${blocks[i].file} block ${blocks[i].index}: ${r.error}`);
    }
});
console.log(`${blocks.length} diagrams parsed, ${failed} failed`);
process.exit(failed ? 1 : 0);
