import { defineConfig } from '@playwright/test';

const port = process.env.KANBAN_E2E_PORT || '8000';
const realtimePort = process.env.KANBAN_E2E_REVERB_PORT || process.env.REVERB_PORT || '8081';

export default defineConfig({
  testDir: 'e2e',
  timeout: 60000,
  workers: 1,
  fullyParallel: false,
  reporter: [['list'], ['json', { outputFile: '.phpunit.cache/enterprise-browser.json' }]],
  use: {
    baseURL: `http://127.0.0.1:${port}`,
    channel: 'msedge',
    headless: true,
  },
  webServer: [
    {
      command: `php vendor/bin/testbench serve --host=127.0.0.1 --port=${port} --no-reload`,
      url: `http://127.0.0.1:${port}/workbench/health`,
      reuseExistingServer: true,
      timeout: 60000,
    },
    {
      command: `php vendor/bin/testbench reverb:start --host=127.0.0.1 --port=${realtimePort}`,
      url: `http://127.0.0.1:${realtimePort}/up`,
      reuseExistingServer: true,
      timeout: 60000,
    },
  ],
});
