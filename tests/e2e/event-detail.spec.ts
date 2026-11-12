import { test, expect } from '@playwright/test';

test.describe('Event Detail & Reporting Workflow', () => {

  test('navigates from homepage to event detail, verifies details & navigation back', async ({ page }) => {
    await page.goto('/');

    // 1. Click first event card
    const firstCardLink = page.locator('a[href^="/akce/"]').first();
    const eventTitle = await firstCardLink.locator('img').getAttribute('alt') || 'Akce';
    await firstCardLink.click();

    // Verify URL changes to /akce/{slug}
    await page.waitForURL('**/akce/**');

    // 2. Verify Event Detail Hero & Content
    await expect(page.locator('h1').first()).toBeVisible();
    
    // Check photographic cover banner is loaded
    const banner = page.locator('img[alt][class*="object-cover"]').first();
    await expect(banner).toBeVisible();

    // Check Map navigation links exist
    const gmapsLink = page.locator('a[href*="google.com/maps"]');
    const mapyLink = page.locator('a[href*="mapy.cz"]');
    await expect(gmapsLink).toBeVisible();
    await expect(mapyLink).toBeVisible();

    // 3. Test Back Navigation without full page reload
    const backBtn = page.locator('a:has-text("Zpět na přehled")').first();
    await expect(backBtn).toBeVisible();
    await backBtn.click();

    await page.waitForURL('**/');
    await expect(page.locator('a[href^="/akce/"]').first()).toBeVisible();
  });

  test('downloads iCal (.ics) calendar file', async ({ page }) => {
    await page.goto('/');
    await page.locator('a[href^="/akce/"]').first().click();
    await page.waitForURL('**/akce/**');

    // Locate iCal download button
    const icalBtn = page.locator('button[wire\\:click="downloadIcal"]').first();
    await expect(icalBtn).toBeVisible();

    // Trigger download and verify
    const downloadPromise = page.waitForEvent('download');
    await icalBtn.click();
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toMatch(/\.ics$/);
  });

  test('submits an issue report for an event', async ({ page }) => {
    await page.goto('/');

    // Accept cookies if banner is present to avoid pointer interception on mobile
    const cookieBtn = page.locator('#cookie_consent_banner button:has-text("Přijmout vše"), #cookie_consent_banner button:has-text("Pouze nezbytné")').first();
    if (await cookieBtn.isVisible()) {
      await cookieBtn.click();
      await page.waitForTimeout(200);
    }

    await page.locator('a[href^="/akce/"]').first().click();
    await page.waitForURL('**/akce/**');

    // Open Report Modal
    const reportBtn = page.locator('button:has-text("Nahlásit")').first();
    await expect(reportBtn).toBeVisible();
    await reportBtn.click();

    // Wait for modal to appear
    await expect(page.locator('text=Nahlásit nesrovnalost nebo chybu')).toBeVisible();

    // Fill out reason (radio)
    const reasonRadio = page.locator('input[type="radio"][value="wrong_time"]').first();
    if (await reasonRadio.isVisible()) {
      await reasonRadio.check();
    }

    // Fill optional description
    const noteArea = page.locator('textarea').first();
    if (await noteArea.isVisible()) {
      await noteArea.fill('Testovací nahlášení v rámci E2E automatických testů.');
    }

    // Submit report
    const submitBtn = page.locator('button:has-text("Odeslat hlášení")').first();
    await submitBtn.click();

    // Verify success confirmation message
    await expect(page.locator('text=Děkujeme za nahlášení!')).toBeVisible({ timeout: 5000 });
  });

  test('opens poster lightbox modal on event detail when clicking full image/poster button', async ({ page }) => {
    await page.goto('/');
    await page.locator('a[href^="/akce/"]').first().click();
    await page.waitForURL('**/akce/**');

    // Click full poster/image button
    const posterBtn = page.locator('button:has-text("Plakát"), button:has-text("Celý plakát"), button:has-text("Obrázek")').first();
    if (await posterBtn.isVisible()) {
      await posterBtn.click();
      const modal = page.locator('#poster_modal');
      await expect(modal).toBeVisible();
      const modalImg = modal.locator('img');
      await expect(modalImg).toBeVisible();
      // Close modal
      const closeBtn = modal.locator('button:has-text("Zavřít")').first();
      await closeBtn.click();
    }
  });

});
