import { test } from '@playwright/test';
import { expectImagesLoaded, expectNoHorizontalScroll, signIn } from './helpers';

/** Viewport checks in Chromium (simulated widths, not real devices). */
const WIDTHS = [320, 375, 390, 768, 1024, 1440, 1920];
const PUBLIC = ['/', '/programmes', '/programmes/agritech-seed-grant-climate-smart-farming', '/projects', '/news', '/community', '/contact', '/sign-up'];

for (const width of WIDTHS) {
    test.describe(`${width}px`, () => {
        test.use({ viewport: { width, height: 900 } });

        test('public pages fit the screen', async ({ page }) => {
            for (const path of PUBLIC) {
                await page.goto(path);
                await expectNoHorizontalScroll(page);
                await expectImagesLoaded(page);
            }
        });

        test('portal and workspace fit the screen', async ({ page }) => {
            await signIn(page, 'member@uew.edu.gh');
            for (const path of ['/portal', '/portal/applications', '/portal/reports', '/notifications']) {
                await page.goto(path);
                await expectNoHorizontalScroll(page);
            }
            await page.context().clearCookies();
            await signIn(page, 'admin@uew.edu.gh');
            for (const path of ['/workspace', '/workspace/reports', '/workspace/progress-reports', '/workspace/applications']) {
                await page.goto(path);
                await expectNoHorizontalScroll(page);
            }
        });
    });
}
