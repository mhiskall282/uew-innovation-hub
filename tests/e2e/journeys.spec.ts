import { expect, test } from '@playwright/test';
import { expectImagesLoaded, signIn, watchConsole } from './helpers';

const PUBLIC_PAGES = ['/', '/programmes', '/projects', '/publications', '/news', '/partners', '/community', '/contact', '/about', '/faq', '/privacy', '/terms', '/sign-in', '/sign-up'];

test.describe('visitor', () => {
    for (const path of PUBLIC_PAGES) {
        test(`${path} loads without errors`, async ({ page }) => {
            const problems = watchConsole(page);
            const response = await page.goto(path);
            expect(response?.status()).toBe(200);
            await expect(page.locator('h1').first()).toBeVisible();
            await expectImagesLoaded(page);
            expect(problems).toEqual([]);
        });
    }

    test('main navigation reaches every section', async ({ page, isMobile }) => {
        await page.goto('/');
        for (const [label, path] of [['Programmes', '/programmes'], ['Projects', '/projects'], ['Publications', '/publications'], ['News', '/news'], ['Partners', '/partners'], ['Contact', '/contact']]) {
            if (isMobile) {
                await page.getByRole('button', { name: 'Open menu' }).click();
                await page.getByRole('navigation', { name: 'Mobile' }).getByRole('link', { name: label, exact: true }).click();
            } else {
                await page.getByRole('navigation', { name: 'Main' }).getByRole('link', { name: label, exact: true }).click();
            }
            await expect(page).toHaveURL(new RegExp(`${path}$`));
        }
    });

    test('unknown pages show the branded 404', async ({ page }) => {
        const response = await page.goto('/no-such-page-here');
        expect(response?.status()).toBe(404);
        await expect(page.getByText(/not found|can.t find/i).first()).toBeVisible();
    });

    test('private areas send visitors to sign in', async ({ page }) => {
        for (const path of ['/workspace', '/portal', '/notifications', '/workspace/reports/pdf']) {
            await page.goto(path);
            await expect(page).toHaveURL(/\/sign-in$/);
        }
    });

    test('wrong password shows a generic error', async ({ page }) => {
        await page.goto('/sign-in');
        await page.getByLabel('E-mail address').fill('member@uew.edu.gh');
        await page.locator('#password').fill('not-the-password');
        await page.getByRole('button', { name: 'Sign in' }).click();
        await expect(page.getByText('These details do not match an active account.')).toBeVisible();
    });

    test('sign-up form validates required fields in the browser and on the server', async ({ page }) => {
        await page.goto('/sign-up');
        await page.getByRole('button', { name: /create account/i }).click();
        // Still on the form: required fields stop submission.
        await expect(page).toHaveURL(/\/sign-up$/);
    });
});

test.describe('member', () => {
    test('signs in, saves an application draft, reads notifications and signs out', async ({ page }) => {
        const problems = watchConsole(page);
        await signIn(page, 'member@uew.edu.gh');
        await expect(page).toHaveURL(/\/portal$/);
        await expect(page.getByRole('heading', { name: /Hello, Abena/ })).toBeVisible();

        await page.goto('/programmes/agritech-seed-grant-climate-smart-farming/apply');
        await page.getByLabel(/Project or idea title/).fill('Solar drip irrigation for Winneba farms');
        await page.getByRole('button', { name: 'Save draft' }).click();
        await expect(page.getByText(/Draft saved/)).toBeVisible();
        await expect(page.getByLabel(/Project or idea title/)).toHaveValue('Solar drip irrigation for Winneba farms');

        await page.goto('/notifications');
        await expect(page.getByRole('heading', { name: 'Notifications', exact: true }).first()).toBeVisible();

        await page.goto('/portal/reports');
        await expect(page.getByRole('link', { name: 'Month 1' })).toBeVisible();

        // Members never get into the workspace, even by URL. The browser logs this
        // intended 403, so console errors are checked before it and reset after.
        expect(problems).toEqual([]);
        const forbidden = await page.goto('/workspace');
        expect(forbidden?.status()).toBe(403);
        problems.length = 0;

        // Sign out through the real form (CSRF token included), then the portal is closed again.
        await page.goto('/portal');
        await Promise.all([
            page.waitForURL((url) => url.pathname === '/'),
            page.locator('form[action$="/sign-out"]').first().evaluate((form: HTMLFormElement) => form.submit()),
        ]);
        await expect(page.getByText('You have been signed out.')).toBeVisible();
        await page.goto('/portal');
        await expect(page).toHaveURL(/\/sign-in$/);
        expect(problems).toEqual([]);
    });
});

test.describe('staff', () => {
    test('reviewer sees only what the role allows', async ({ page }) => {
        await signIn(page, 'reviewer@uew.edu.gh');
        await expect(page).toHaveURL(/\/workspace$/);
        const nav = page.getByRole('complementary', { name: 'Workspace navigation' });
        await expect(nav.getByRole('link', { name: 'Applications' })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Staff accounts' })).toHaveCount(0);
        await expect(nav.getByRole('link', { name: 'Site settings' })).toHaveCount(0);

        const denied = await page.goto('/workspace/users');
        expect(denied?.status()).toBe(403);
    });

    test('reports change with the period and export a PDF', async ({ page }) => {
        await signIn(page, 'admin@uew.edu.gh');
        await page.goto('/workspace/reports');
        await page.getByRole('link', { name: 'All time' }).click();
        await expect(page).toHaveURL(/range=all/);
        await expect(page.getByText(/All time, to/).first()).toBeVisible();

        await page.getByRole('button', { name: 'Custom range' }).click();
        await page.locator('#range-from').fill('2026-01-01');
        await page.getByRole('button', { name: 'Apply' }).click();
        await expect(page).toHaveURL(/range=custom&from=2026-01-01/);

        await page.getByRole('button', { name: 'Export PDF' }).click();
        const [download] = await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Download PDF' }).click()]);
        expect(download.suggestedFilename()).toMatch(/^uew-innovation-hub-report-\d{8}-\d{8}\.pdf$/);
    });
});
