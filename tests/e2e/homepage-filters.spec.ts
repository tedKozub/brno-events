import { test, expect } from '@playwright/test';

test.describe('Homepage & Event Filters Workflow', () => {

  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    // Wait for event cards to load
    await expect(page.locator('a[href^="/akce/"]').first()).toBeVisible({ timeout: 10000 });
  });

  test('renders homepage elements: logo, search input, stats, view switcher', async ({ page }) => {
    // Brand header
    await expect(page.locator('text=AKCEBRNO').first()).toBeVisible();

    // Search bar
    const searchInput = page.locator('input[placeholder*="Hledat akci"]');
    await expect(searchInput).toBeVisible();

    // View switcher buttons
    await expect(page.locator('button:has-text("Přehled")').first()).toBeVisible();
    await expect(page.locator('button:has-text("Mapa Brna")').first()).toBeVisible();

    // Event cards present
    const eventCards = page.locator('a[href^="/akce/"]');
    const count = await eventCards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('filters events reactively by text search', async ({ page }) => {
    const searchInput = page.locator('input[placeholder*="Hledat akci"]');
    
    // Type search query
    await searchInput.fill('Křehká svoboda');
    
    // Wait for debounce and Livewire re-render
    await page.waitForTimeout(600);

    // Verify filtered card is visible
    await expect(page.locator('text=Křehká svoboda 2026').first()).toBeVisible();

    // Clear search
    await searchInput.fill('');
    await page.waitForTimeout(600);

    // Multiple cards visible again
    const countAfter = await page.locator('a[href^="/akce/"]').count();
    expect(countAfter).toBeGreaterThan(1);
  });

  test('filters events by category chips', async ({ page }) => {
    // Find all category buttons
    const categoryButtons = page.locator('button[wire\\:click*="setCategory"]');
    const catCount = await categoryButtons.count();
    expect(catCount).toBeGreaterThan(1);

    // Click on a specific category (e.g. Přednášky & Kariéra)
    const lectureBtn = page.locator('button[wire\\:click*="prednasky-kariera"], button:has-text("Přednášky & Kariéra")').first();
    if (await lectureBtn.isVisible()) {
      await lectureBtn.click();
      await page.waitForTimeout(500);

      // Verify category button becomes active (has primary styling)
      await expect(lectureBtn).toHaveClass(/bg-primary|border-primary/);
    }

    // Reset back to "Všechny kategorie"
    const allBtn = page.locator('button:has-text("Všechny kategorie")').first();
    await allBtn.click();
    await page.waitForTimeout(500);
    await expect(allBtn).toHaveClass(/bg-primary/);
  });

  test('filters events by university', async ({ page }) => {
    const muniBtn = page.locator('button:has-text("MUNI")').first();
    await expect(muniBtn).toBeVisible();

    await muniBtn.click();
    await page.waitForTimeout(500);

    // Verify active styling
    await expect(muniBtn).toHaveClass(/bg-neutral/);

    // Reset back to all universities
    const allUniBtn = page.locator('button:has-text("Všechny")').first();
    await allUniBtn.click();
    await page.waitForTimeout(500);
  });

  test('toggles free-only events filter', async ({ page }) => {
    const freeToggle = page.locator('#filter-only-free');
    await expect(freeToggle).toBeVisible();

    // Check toggle
    await freeToggle.click();
    await page.waitForTimeout(500);

    // In free mode, events shown should have "Zdarma" or voluntary badge
    const priceBadges = page.locator('text=Zdarma');
    const freeCount = await priceBadges.count();
    expect(freeCount).toBeGreaterThan(0);

    // Uncheck toggle
    await freeToggle.click();
    await page.waitForTimeout(500);
  });

  test('switches between cards overview and interactive map mode', async ({ page }) => {
    const mapBtn = page.locator('button:has-text("Mapa Brna")').first();
    await mapBtn.click();
    await page.waitForTimeout(600);

    // Verify map section is rendered
    await expect(page.locator('text=Místa akcí a kluby v Brně').first()).toBeVisible();
    await expect(page.locator('div[x-ref="mapContainer"]')).toBeAttached();

    // Switch back to cards overview
    const cardsBtn = page.locator('button:has-text("Přehled")').first();
    await cardsBtn.click();
    await page.waitForTimeout(500);

    await expect(page.locator('a[href^="/akce/"]').first()).toBeVisible();
  });

});
