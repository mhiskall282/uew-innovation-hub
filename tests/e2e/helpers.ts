import { expect, type Page } from '@playwright/test';

/** Synthetic demo accounts seeded into the isolated e2e database (password from SEED_PASSWORD). */
export const PASSWORD = 'password';

export async function signIn(page: Page, email: string): Promise<void> {
    await page.goto('/sign-in');
    await page.getByLabel('E-mail address').fill(email);
    await page.locator('#password').fill(PASSWORD);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await page.waitForURL((url) => !url.pathname.startsWith('/sign-in'));
}

/** Fails the test on uncaught JS errors and on CSP or console errors. */
export function watchConsole(page: Page): string[] {
    const problems: string[] = [];
    page.on('pageerror', (e) => problems.push(`pageerror: ${e.message}`));
    page.on('console', (m) => {
        if (m.type() === 'error') problems.push(`console: ${m.text()}`);
    });
    return problems;
}

export async function expectNoHorizontalScroll(page: Page): Promise<void> {
    const overflow = await page.evaluate(() => {
        const doc = document.documentElement;
        return { scroll: doc.scrollWidth, client: doc.clientWidth };
    });
    expect(overflow.scroll, `page is ${overflow.scroll}px wide in a ${overflow.client}px viewport`).toBeLessThanOrEqual(overflow.client + 1);
}

export async function expectImagesLoaded(page: Page): Promise<void> {
    const broken = await page.evaluate(async () => {
        const imgs = Array.from(document.images).filter((i) => i.loading !== 'lazy' || i.getBoundingClientRect().top < innerHeight);
        await Promise.all(imgs.map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
        return imgs.filter((i) => i.naturalWidth === 0).map((i) => i.currentSrc || i.src);
    });
    expect(broken, `broken images: ${broken.join(', ')}`).toEqual([]);
}
