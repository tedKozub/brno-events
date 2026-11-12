import { test, expect } from '@playwright/test';

test.describe('Feed Dynamic Fetching, Nav Highlighting & Scroll To Top', () => {

  test('nav item "Akce" remains highlighted when switching to "Mapa Brna"', async ({ page }) => {
    await page.goto('/');

    // Check "Akce" nav link is styled active
    const akceLink = page.locator('nav a[href="/"]').first();
    await expect(akceLink).toHaveClass(/bg-primary/);

    // Switch to "Mapa Brna"
    const mapSwitcherBtn = page.locator('button:has-text("Mapa Brna")').first();
    await expect(mapSwitcherBtn).toBeVisible();
    await mapSwitcherBtn.click();
    await page.waitForTimeout(500);

    // Verify map is visible
    await expect(page.locator('text=Místa akcí a kluby v Brně').first()).toBeVisible();

    // Verify "Akce" nav item is STILL styled active
    await expect(akceLink).toHaveClass(/bg-primary/);
  });

  test('feed initially renders first chunk (12 events) and shows load more button', async ({ page }) => {
    await page.goto('/');

    // Accept cookies if banner is visible to prevent overlay on mobile
    const acceptCookies = page.locator('#cookie_consent_banner button:has-text("Přijmout vše"), #cookie_consent_banner button:has-text("Pouze nezbytné")').first();
    if (await acceptCookies.isVisible()) {
      await acceptCookies.click();
      await page.waitForTimeout(200);
    }

    // Wait for event cards
    const cards = page.locator('div[wire\\:key^="event-card-"]');
    await expect(cards.first()).toBeVisible({ timeout: 10000 });

    const initialCount = await cards.count();
    expect(initialCount).toBe(12);

    // Load more button is visible
    const loadMoreBtn = page.locator('button:has-text("Načíst další akce")');
    await expect(loadMoreBtn).toBeVisible();
    await expect(loadMoreBtn).toContainText('zobrazeno 12 z');

    // Click load more button
    await loadMoreBtn.click();
    await page.waitForTimeout(800);

    // Verify more cards are loaded
    const updatedCount = await cards.count();
    expect(updatedCount).toBeGreaterThan(12);
  });

  test('scroll-to-top floating button appears on scroll and scrolls back to top', async ({ page }) => {
    await page.goto('/');

    // Accept cookies if banner is visible to prevent overlay
    const acceptCookies = page.locator('#cookie_consent_banner button:has-text("Přijmout vše"), #cookie_consent_banner button:has-text("Pouze nezbytné")').first();
    if (await acceptCookies.isVisible()) {
      await acceptCookies.click();
      await page.waitForTimeout(200);
    }

    // Initially at top: scroll-to-top button should not be visible
    const scrollBtn = page.locator('button[aria-label="Zpět nahoru"]');
    await expect(scrollBtn).not.toBeVisible();

    // Scroll down 600px
    await page.evaluate(() => {
      window.scrollTo(0, 600);
      window.dispatchEvent(new Event('scroll'));
    });
    await page.waitForTimeout(400);

    // Now button should be visible
    await expect(scrollBtn).toBeVisible();

    // Click scroll-to-top button
    await scrollBtn.click();

    // Wait for smooth scrolling to reach top
    await page.waitForFunction(() => (window.pageYOffset || document.documentElement.scrollTop) <= 50, { timeout: 3000 });

    // Verify scroll position is back at 0
    const scrollY = await page.evaluate(() => window.pageYOffset || document.documentElement.scrollTop);
    expect(scrollY).toBeLessThanOrEqual(50);
  });

});
