import { test, expect } from '@playwright/test';

test.describe('Data Serapan PTN Page (Redirect to Integrated)', () => {
  test('should redirect to integrated Prestasi Akademik page', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik/serapan-ptn');
    
    await expect(page).toHaveURL('/akademik/prestasi-akademik');
    await expect(page).toHaveTitle(/Prestasi Akademik/);
  });

  test('should load without console errors after redirect', async ({ page }) => {
    const errors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });
    page.on('pageerror', (error) => {
      errors.push(error.message);
    });

    await page.goto('/akademik/prestasi-akademik/serapan-ptn');
    await page.waitForLoadState('networkidle');

    expect(errors).toHaveLength(0);
  });

  test('should display Serapan PTN section on integrated page', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik/serapan-ptn');
    
    await expect(page.locator('h2:has-text("Data Serapan PTN")')).toBeVisible();
  });
});
