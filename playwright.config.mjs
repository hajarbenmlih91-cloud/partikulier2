import { defineConfig, devices } from 'playwright';

/** Configuration commune des recettes navigateur Partikulier. */
export default defineConfig({
  testDir: './tests',
  timeout: 120000,
  fullyParallel: false,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['json', { outputFile: 'documentation/playwright-results.json' }]],
  use: {
    baseURL: process.env.PK_BASE || 'https://blanchedalmond-reindeer-376379.hostingersite.com',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    locale: 'fr-FR',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit', use: { ...devices['Desktop Safari'] } },
  ],
});
