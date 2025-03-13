import dotenv from 'dotenv';
import path from 'path';
import {defineConfig, devices} from '@playwright/test';

dotenv.config({
  path: path.resolve(process.cwd(), '.env')
});

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: 'playwright/tests',
  outputDir: 'playwright/results',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [[
    'html', {outputFolder: 'playwright/reports', open: 'never'}
  ]],
  use: {
    headless: true,
    baseURL: process.env.APP_URL,
    trace: 'on-first-retry',
  },

  projects: [{
    name: 'chromium',
    use: {...devices['Desktop Chrome']},
  // }, {
  //   name: 'firefox',
  //   use: {...devices['Desktop Firefox']},
  // }, {
  //   name: 'webkit',
  //   use: {...devices['Desktop Safari']},
  }],
});

