import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { signIn } from './helpers';

/** Automated WCAG 2.2 A/AA checks with axe-core. Manual checks are listed in docs/audit/ACCESSIBILITY_AUDIT_REPORT.md. */
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

async function audit(page: import('@playwright/test').Page, label: string) {
    const results = await new AxeBuilder({ page }).withTags(TAGS).analyze();
    const summary = results.violations.map((v) => `${v.id} (${v.impact}): ${v.nodes.length}× ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`);
    expect(summary, `${label}\n${summary.join('\n')}`).toEqual([]);
}

test.describe('accessibility', () => {
    for (const path of ['/', '/programmes', '/programmes/agritech-seed-grant-climate-smart-farming', '/projects', '/publications', '/news', '/partners', '/community', '/contact', '/about', '/sign-in', '/sign-up', '/forgot-password']) {
        test(`public ${path}`, async ({ page }) => {
            await page.goto(path);
            await audit(page, path);
        });
    }

    test('member portal', async ({ page }) => {
        await signIn(page, 'member@uew.edu.gh');
        for (const path of ['/portal', '/portal/applications', '/portal/reports', '/portal/profile', '/notifications', '/programmes/agritech-seed-grant-climate-smart-farming/apply']) {
            await page.goto(path);
            await audit(page, path);
        }
    });

    test('staff workspace', async ({ page }) => {
        await signIn(page, 'admin@uew.edu.gh');
        for (const path of ['/workspace', '/workspace/reports', '/workspace/progress-reports', '/workspace/applications', '/workspace/programmes', '/workspace/members', '/workspace/settings']) {
            await page.goto(path);
            await audit(page, path);
        }
    });

    test('keyboard: skip link and visible focus', async ({ page }) => {
        await page.goto('/');
        await page.keyboard.press('Tab');
        const skip = page.getByRole('link', { name: 'Skip to content' });
        await expect(skip).toBeFocused();
        await expect(skip).toBeVisible();
        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(/#main$/);

        const outline = await page.evaluate(() => {
            const link = document.querySelector('header a') as HTMLElement;
            link.focus();
            const s = getComputedStyle(link);
            return s.outlineStyle !== 'none' || s.boxShadow !== 'none';
        });
        expect(outline).toBe(true);
    });
});
