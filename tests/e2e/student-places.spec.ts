import { test, expect } from '@playwright/test';

test.describe('Student Places & Map Workflow (/mista)', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/mista');
    await expect(page.locator('#student-places-map')).toBeVisible({ timeout: 10000 });
  });

  test('initializes student places map with Leaflet canvas', async ({ page }) => {
    // Map container exists
    const mapContainer = page.locator('#student-places-map');
    await expect(mapContainer).toBeVisible();

    // Leaflet classes added
    await expect(page.locator('#student-places-map.leaflet-container')).toBeVisible({ timeout: 10000 });

    // Place cards rendered
    const placeCards = page.locator('button:has-text("Na mapě")');
    const count = await placeCards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('filters places by category', async ({ page }) => {
    const studyRoomsBtn = page.locator('button:has-text("Studovny")').first();
    await expect(studyRoomsBtn).toBeVisible();

    await studyRoomsBtn.click();
    await page.waitForTimeout(500);

    // Verify active styling
    await expect(studyRoomsBtn).toHaveClass(/bg-primary|text-primary-content/);

    // Reset back to all places
    const allBtn = page.locator('button:has-text("Všechna místa")').first();
    await allBtn.click();
    await page.waitForTimeout(500);
  });

  test('filters places reactively by text search', async ({ page }) => {
    const searchInput = page.locator('input[placeholder*="Hledat"]');
    await searchInput.fill('Knihovna');
    await page.waitForTimeout(600);

    // Filtered cards visible
    const cards = page.locator('button:has-text("Na mapě")');
    const filteredCount = await cards.count();
    expect(filteredCount).toBeGreaterThan(0);

    // Clear search
    await searchInput.fill('');
    await page.waitForTimeout(600);
  });

  test('clicking "Na mapě" interacts with map and opens popup', async ({ page }) => {
    const firstPlaceBtn = page.locator('button:has-text("Na mapě")').first();
    await expect(firstPlaceBtn).toBeVisible();
    await firstPlaceBtn.click();

    // Map fly-to and popup display
    await page.waitForTimeout(1000);
    const popup = page.locator('.leaflet-popup');
    await expect(popup).toBeVisible({ timeout: 5000 });
  });

});
