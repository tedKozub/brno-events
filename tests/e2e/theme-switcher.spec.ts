import { test, expect } from '@playwright/test';

test.describe('Theme Switcher Workflow', () => {

  test('rotates theme: system -> dark -> light -> system', async ({ page }) => {
    await page.goto('/');

    const themeBtn = page.locator('button[aria-label="Přepnout motiv vzhledu"]').first();
    await expect(themeBtn).toBeVisible();

    // 1. Initial state (system)
    const initialPref = await page.evaluate(() => localStorage.getItem('brno_events_theme') || 'system');
    expect(['system', 'light', 'dark', 'events-light', 'events-dark']).toContain(initialPref);

    // 2. Click to Dark
    await themeBtn.click();
    await page.waitForTimeout(300);

    const darkAttr = await page.locator('html').getAttribute('data-theme');
    const darkStorage = await page.evaluate(() => localStorage.getItem('brno_events_theme'));
    expect(darkAttr).toBe('events-dark');
    expect(darkStorage).toBe('dark');

    // 3. Click to Light
    await themeBtn.click();
    await page.waitForTimeout(300);

    const lightAttr = await page.locator('html').getAttribute('data-theme');
    const lightStorage = await page.evaluate(() => localStorage.getItem('brno_events_theme'));
    expect(lightAttr).toBe('events-light');
    expect(lightStorage).toBe('light');

    // 4. Click back to System
    await themeBtn.click();
    await page.waitForTimeout(300);

    const systemStorage = await page.evaluate(() => localStorage.getItem('brno_events_theme'));
    expect(systemStorage).toBe('system');
  });

  test('persists theme preference across page navigation', async ({ page }) => {
    await page.goto('/');

    const themeBtn = page.locator('button[aria-label="Přepnout motiv vzhledu"]').first();
    // Switch to dark
    await themeBtn.click();
    await page.waitForTimeout(300);
    expect(await page.locator('html').getAttribute('data-theme')).toBe('events-dark');

    // Navigate to student places (/mista)
    await page.click('a[href="/mista"]');
    await page.waitForURL('**/mista');
    await page.waitForTimeout(400);

    // Verify dark theme is maintained
    expect(await page.locator('html').getAttribute('data-theme')).toBe('events-dark');

    // Return to homepage
    await page.click('a[href="/"]');
    await page.waitForURL('**/');
    await page.waitForTimeout(400);

    // Still dark
    expect(await page.locator('html').getAttribute('data-theme')).toBe('events-dark');

    // Clean up to system
    await page.evaluate(() => {
      localStorage.setItem('brno_events_theme', 'system');
      if (window['applyAppTheme']) window['applyAppTheme']();
    });
  });

});
