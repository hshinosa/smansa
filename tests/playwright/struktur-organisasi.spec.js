import { test, expect } from '@playwright/test';

test.describe('Struktur Organisasi Page', () => {
  test('should load without console errors', async ({ page }) => {
    const errors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });
    page.on('pageerror', (error) => {
      errors.push(error.message);
    });

    await page.goto('/struktur-organisasi');
    await page.waitForLoadState('networkidle');

    expect(errors).toHaveLength(0);
  });

  test('should display page title and heading', async ({ page }) => {
    await page.goto('/struktur-organisasi');
    
    await expect(page).toHaveTitle(/Struktur Organisasi/);
    await expect(page.locator('h1')).toContainText('Struktur Organisasi');
  });

  test('should load hero image without 404', async ({ page }) => {
    const failedRequests = [];
    page.on('response', (response) => {
      if (response.status() === 404) {
        failedRequests.push(response.url());
      }
    });

    await page.goto('/struktur-organisasi');
    await page.waitForLoadState('networkidle');

    const image404s = failedRequests.filter(url => 
      url.includes('.jpg') || url.includes('.jpeg') || url.includes('.png') || url.includes('.webp')
    );
    
    expect(image404s).toHaveLength(0);
  });
});
