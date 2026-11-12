import { test, expect } from '@playwright/test';

test.describe('Mobile Viewport & Layout Integrity', () => {

  test('homepage has no horizontal overflow on mobile viewport', async ({ page }) => {
    await page.goto('/');
    await page.waitForTimeout(600);

    // Verify document width does not cause horizontal scrolling
    const hasHorizontalOverflow = await page.evaluate(() => {
      return document.documentElement.scrollWidth > document.documentElement.clientWidth;
    });

    expect(hasHorizontalOverflow).toBe(false);
  });

  test('event detail action buttons fit mobile screen without clipping', async ({ page }) => {
    await page.goto('/');
    const firstCard = page.locator('a[href^="/akce/"]').first();
    await firstCard.click();
    await page.waitForURL('**/akce/**');

    // Verify no horizontal overflow on detail page
    const hasHorizontalOverflow = await page.evaluate(() => {
      return document.documentElement.scrollWidth > document.documentElement.clientWidth;
    });
    expect(hasHorizontalOverflow).toBe(false);

    // Verify Google Maps and Mapy.cz buttons are side-by-side or cleanly stacked
    const gmapsBtn = page.locator('a[href*="google.com/maps"]');
    const mapyBtn = page.locator('a[href*="mapy.cz"]');
    await expect(gmapsBtn).toBeVisible();
    await expect(mapyBtn).toBeVisible();

    const gmapsBox = await gmapsBtn.boundingBox();
    const mapyBox = await mapyBtn.boundingBox();
    const viewportWidth = page.viewportSize()?.width || 390;

    if (gmapsBox && mapyBox) {
      expect(gmapsBox.x + gmapsBox.width).toBeLessThanOrEqual(viewportWidth + 2);
      expect(mapyBox.x + mapyBox.width).toBeLessThanOrEqual(viewportWidth + 2);
    }
  });

  test('student places page (/mista) cards and map fit mobile screen', async ({ page }) => {
    await page.goto('/mista');
    await page.waitForTimeout(600);

    const hasHorizontalOverflow = await page.evaluate(() => {
      return document.documentElement.scrollWidth > document.documentElement.clientWidth;
    });
    expect(hasHorizontalOverflow).toBe(false);

    // Verify map canvas fits within viewport
    const mapBox = await page.locator('#student-places-map').boundingBox();
    const viewportWidth = page.viewportSize()?.width || 390;
    if (mapBox) {
      expect(mapBox.width).toBeLessThanOrEqual(viewportWidth);
    }
  });

  test('theme toggle button is single compact rotating button on mobile', async ({ page }) => {
    await page.goto('/');

    const themeBtn = page.locator('button[aria-label="Přepnout motiv vzhledu"]').first();
    await expect(themeBtn).toBeVisible();

    const box = await themeBtn.boundingBox();
    // Compact button (around 36-44px wide and high, not a wide dropdown)
    if (box) {
      expect(box.width).toBeLessThanOrEqual(60);
      expect(box.height).toBeLessThanOrEqual(60);
    }
  });

});
