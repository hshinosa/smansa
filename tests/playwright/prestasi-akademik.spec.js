import { test, expect } from '@playwright/test';

test.describe('Prestasi Akademik Page (Integrated)', () => {
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

    await page.goto('/akademik/prestasi-akademik');
    await page.waitForLoadState('networkidle');

    expect(errors).toHaveLength(0);
  });

  test('should display page title and heading', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    await expect(page).toHaveTitle(/Prestasi Akademik/);
    await expect(page.locator('h1')).toContainText('Prestasi Akademik');
  });

  test('should have sidebar navigation with all sections', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    const sidebarNav = page.locator('aside nav');
    await expect(sidebarNav).toContainText('Ringkasan');
    await expect(sidebarNav).toContainText('Serapan PTN');
    await expect(sidebarNav).toContainText('Hasil Ujian');
  });

  test('should display overview stats cards', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    const overviewSection = page.locator('#overview');
    await expect(overviewSection.locator('text=Total Siswa Diterima')).toBeVisible();
    await expect(overviewSection.locator('text=Total PTN').first()).toBeVisible();
    await expect(overviewSection.locator('text=PTN Favorit').first()).toBeVisible();
    await expect(overviewSection.locator('text=Rata-rata Nilai')).toBeVisible();
  });

  test('should display Serapan PTN section with batches', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    await expect(page.locator('h2:has-text("Data Serapan PTN")')).toBeVisible();
  });

  test('should display Hasil Ujian section with filter buttons', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    await expect(page.locator('h2:has-text("Hasil Ujian")')).toBeVisible();
    
    const filterButtons = page.locator('button').filter({ hasText: /20\d{2}\/\d{4}/ });
    await expect(filterButtons.first()).toBeVisible();
  });

  test('should display chart and table in Hasil Ujian section', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    const hasilTkaSection = page.locator('#hasil-tka');
    await expect(hasilTkaSection.locator('canvas')).toBeVisible();
    await expect(hasilTkaSection.locator('table')).toBeVisible();
    await expect(hasilTkaSection.locator('th:has-text("Mata Pelajaran")')).toBeVisible();
    await expect(hasilTkaSection.locator('th:has-text("Nilai Rata-rata")')).toBeVisible();
    await expect(hasilTkaSection.locator('th:has-text("Status")')).toBeVisible();
  });

  test('should have working scroll navigation', async ({ page }) => {
    await page.goto('/akademik/prestasi-akademik');
    
    await page.click('button:has-text("Serapan PTN")');
    await page.waitForTimeout(500);
    
    await expect(page.locator('h2:has-text("Data Serapan PTN")')).toBeInViewport();
  });

  test('sidebar should be sticky on desktop', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/akademik/prestasi-akademik');
    
    const sidebar = page.locator('aside');
    await expect(sidebar).toBeVisible();
    
    await page.evaluate(() => window.scrollTo(0, 500));
    await page.waitForTimeout(300);
    
    await expect(sidebar).toBeInViewport();
  });

  test('should be responsive on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/akademik/prestasi-akademik');
    
    await expect(page.locator('h1')).toContainText('Prestasi Akademik');
    await expect(page.locator('text=Total Siswa Diterima')).toBeVisible();
  });
});
