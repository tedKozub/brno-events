import { test, expect } from '@playwright/test';

test.describe('Legal Framework, GDPR & Cookie Consent', () => {

    test('cookie consent banner shows up for new visitors and can be accepted', async ({ page }) => {
        // Navigate to homepage
        await page.goto('/');

        // Wait for cookie banner to appear
        const banner = page.locator('#cookie_consent_banner');
        await expect(banner).toBeVisible({ timeout: 5000 });
        await expect(banner).toContainText('Ochrana soukromí & Cookies');

        // Click "Pouze nezbytné"
        const essentialBtn = banner.getByRole('button', { name: 'Pouze nezbytné' });
        await essentialBtn.click();

        // Banner should disappear
        await expect(banner).not.toHaveClass(/translate-y-0/);

        // Verify localStorage value
        const stored = await page.evaluate(() => localStorage.getItem('brno_events_cookie_consent'));
        expect(stored).not.toBeNull();
        const parsed = JSON.parse(stored || '{}');
        expect(parsed.essential).toBe(true);
        expect(parsed.analytics).toBe(false);
    });

    test('footer link opens cookie settings modal and allows toggling analytics', async ({ page }) => {
        await page.goto('/');

        // Click footer "Nastavení cookies"
        const footerBtn = page.getByRole('button', { name: 'Nastavení cookies' });
        await footerBtn.scrollIntoViewIfNeeded();
        await footerBtn.click();

        // Modal should open
        const modal = page.locator('#cookie_settings_modal');
        await expect(modal).toBeVisible();
        await expect(modal).toContainText('Předvolby soukromí a cookies');

        // Toggle analytics checkbox
        const analyticsCheckbox = page.locator('#cookie_pref_analytics');
        await analyticsCheckbox.check();

        // Save preferences
        const saveBtn = modal.getByRole('button', { name: 'Uložit předvolby' });
        await saveBtn.click();

        // Verify localStorage updated
        const stored = await page.evaluate(() => localStorage.getItem('brno_events_cookie_consent'));
        const parsed = JSON.parse(stored || '{}');
        expect(parsed.analytics).toBe(true);
    });

    test('terms of use page renders with copyright law and notice & takedown', async ({ page }) => {
        await page.goto('/podminky');

        await expect(page).toHaveTitle(/Podmínky užití, autorská práva/i);
        await expect(page.locator('h1')).toContainText('Podmínky užití & Autorská práva');

        // Verify Notice and Takedown section
        const takedownSection = page.locator('#takedown');
        await expect(takedownSection).toBeVisible();
        await expect(takedownSection).toContainText('legal@brnoakce.cz');
        await expect(takedownSection).toContainText('Notice & Takedown');

        // Check back link to homepage
        const backLink = page.getByRole('link', { name: /Zpět na přehled akcí|Zpět/i }).first();
        await backLink.click();
        await expect(page).toHaveURL('/');
    });

    test('privacy policy page renders with GDPR details and supervisory authority', async ({ page }) => {
        await page.goto('/ochrana-soukromi');

        await expect(page).toHaveTitle(/Zásady ochrany osobních údajů/i);
        await expect(page.locator('h1')).toContainText('Zásady ochrany soukromí');
        await expect(page.getByText('Úřad pro ochranu osobních údajů')).toBeVisible();
        await expect(page.getByText('privacy@brnoakce.cz').first()).toBeVisible();
    });

    test('event cards have valid loading cover images without broken links', async ({ page }) => {
        await page.goto('/');

        const eventImages = page.locator('.grid a.relative img');
        await expect(eventImages.first()).toBeVisible({ timeout: 5000 });

        const count = await eventImages.count();
        expect(count).toBeGreaterThan(0);

        // Check first 3 images, scrolling each into view so lazy loading triggers
        const checkCount = Math.min(count, 3);
        for (let i = 0; i < checkCount; i++) {
            const img = eventImages.nth(i);
            await img.scrollIntoViewIfNeeded();
            await expect.poll(async () => {
                return await img.evaluate((el: HTMLImageElement) => el.complete && el.naturalWidth > 0);
            }, { timeout: 6000 }).toBe(true);
        }
    });
});
