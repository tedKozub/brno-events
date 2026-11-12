import { test, expect } from '@playwright/test';

test.describe('PWA Install Guide & Footer Workflows', () => {

  test('opens PWA install guide modal and verifies iPhone 3-step instructions', async ({ page }) => {
    await page.goto('/');

    // Locate footer PWA button
    const pwaBtn = page.locator('button:has-text("Přidat na plochu / PWA")').first();
    await expect(pwaBtn).toBeVisible();

    // Click to open modal
    await pwaBtn.click();

    // Verify modal dialog opens
    const modal = page.locator('#pwa_install_modal');
    await expect(modal).toHaveAttribute('open');
    await expect(page.locator('text=Nainstalovat BrnoAkce')).toBeVisible();

    // Verify 3 steps are present
    await expect(page.locator('text=1. V Safari klikni na Sdílet')).toBeVisible();
    await expect(page.locator('text=2. Zvol „Přidat na plochu“')).toBeVisible();
    await expect(page.locator('text=3. Vpravo nahoře potvrď „Přidat“')).toBeVisible();

    // Close modal
    const closeBtn = modal.locator('button:has-text("Rozumím")');
    await closeBtn.click();
    await page.waitForTimeout(300);

    // Verify modal closed
    await expect(modal).not.toHaveAttribute('open');
  });

  test('footer contains working link to admin/spolky login', async ({ page }) => {
    await page.goto('/');

    const spolkyLink = page.locator('a:has-text("Vstup pro spolky")').first();
    await expect(spolkyLink).toBeVisible();

    await spolkyLink.click();
    await page.waitForURL('**/admin**');

    // Check Filament login elements
    await expect(page.locator('input[type="email"], input[id*="email"]').first()).toBeVisible();
  });

});
