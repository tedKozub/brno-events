import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 2 : undefined,
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: 'playwright-report' }]
  ],
  use: {
    baseURL: 'http://127.0.0.1:8085',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    extraHTTPHeaders: {
      'X-E2E-Testing': 'true',
    },
  },
  projects: [
    // 1. Desktop Workflows
    {
      name: 'Desktop Chrome',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 800 },
      },
    },

    // 2. Mobile Workflows (iPhone 14 / Safari)
    {
      name: 'Mobile iPhone 14',
      use: {
        ...devices['iPhone 14'],
      },
    },

    // 3. Mobile Workflows (Pixel 7 / Android Chrome)
    {
      name: 'Mobile Pixel 7',
      use: {
        ...devices['Pixel 7'],
      },
    },
  ],
});
