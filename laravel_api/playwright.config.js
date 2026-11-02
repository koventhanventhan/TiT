import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  timeout: 60000,
  fullyParallel: false,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: 'http://localhost:4173',
  },
  webServer: {
    command: 'npm run preview -- --outDir .. --host',
    port: 4173,
    reuseExistingServer: true,
  },
  projects: [
    {
      name: 'chromium',
      testIgnore: '**/frontend-visual.spec.js',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'frontend-visual',
      testMatch: '**/frontend-visual.spec.js',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
