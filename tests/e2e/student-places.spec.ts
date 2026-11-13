import { test, expect } from '@playwright/test';

test.describe('Student Places & Map Workflow (/mista)', () => {

  test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
      localStorage.setItem('brno_events_cookie_consent', JSON.stringify({
        necessary: true,
        analytics: true,
        timestamp: Date.now(),
      }));
    });
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

  test('supports touchpad scroll/pinch gestures and mobile touch interactions on the map', async ({ page, isMobile }) => {
    const mapEl = page.locator('#student-places-map');
    await expect(mapEl).toBeVisible();

    // Ensure Leaflet map has finished asynchronous Alpine $nextTick initialization
    await page.waitForFunction(() => {
      const el = document.getElementById('student-places-map');
      // @ts-ignore
      const alpineData = window.Alpine?.$data(el?.closest('[x-data]'));
      return Boolean(alpineData?.map && alpineData?.map?.options);
    }, { timeout: 10000 });

    // Verify Leaflet instance options enable touchpad and mobile gestures
    const gestureOptions = await page.evaluate(() => {
      const el = document.getElementById('student-places-map');
      // @ts-ignore
      const alpineData = window.Alpine?.$data(el?.closest('[x-data]'));
      const map = alpineData?.map;
      return {
        scrollWheelZoom: map?.options?.scrollWheelZoom,
        touchZoom: map?.options?.touchZoom,
        dragging: map?.options?.dragging,
        tap: map?.options?.tap,
      };
    });

    expect(gestureOptions.scrollWheelZoom).toBe(true);
    expect(gestureOptions.touchZoom).toBe(true);
    expect(gestureOptions.dragging).toBe(true);

    if (!isMobile) {
      // Test mouse wheel / touchpad zoom gesture simulation on desktop
      const initialZoom = await page.evaluate(() => {
        // @ts-ignore
        const alpineData = window.Alpine?.$data(document.getElementById('student-places-map')?.closest('[x-data]'));
        return alpineData?.map?.getZoom();
      });

      await mapEl.hover();
      await page.mouse.wheel(0, -120);
      await page.waitForTimeout(500);

      const newZoom = await page.evaluate(() => {
        // @ts-ignore
        const alpineData = window.Alpine?.$data(document.getElementById('student-places-map')?.closest('[x-data]'));
        return alpineData?.map?.getZoom();
      });

      expect(newZoom).toBeGreaterThanOrEqual(initialZoom);
    } else {
      // Test mobile touch interaction
      const box = await mapEl.boundingBox();
      if (box) {
        await page.touchscreen.tap(box.x + box.width / 2, box.y + box.height / 2);
      }
    }
  });

  test('opens propose place modal and submits crowdsourced suggestion', async ({ page }) => {
    const proposeBtn = page.locator('#btn-propose-place');
    await expect(proposeBtn).toBeVisible();
    await proposeBtn.click();

    // Modal dialog is displayed
    const modalTitle = page.locator('#propose-place-title');
    await expect(modalTitle).toBeVisible();

    // Fill form
    await page.fill('#propose-name', 'E2E Testovací Kavárna');
    await page.fill('#propose-address', 'Gorkého 15, Brno');
    await page.fill('#propose-description', 'Skvělá studentská káva a příjemná zahrádka.');

    // Submit
    const submitBtn = page.locator('#submit-propose-place');
    await submitBtn.scrollIntoViewIfNeeded();
    await submitBtn.click();

    // Success screen
    await expect(page.locator('text=Díky moc za tip!')).toBeVisible({ timeout: 10000 });
  });

});
