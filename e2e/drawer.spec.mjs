import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

test('drawer shares Board/List details, traps focus and fills the mobile viewport', async ({ page }) => {
  test.setTimeout(120000);
  if (!process.env.WORKBENCH_DB?.replaceAll('\\', '/').includes('/.phpunit.cache/')) throw new Error('Use an isolated test database');
  execFileSync('php', ['vendor/bin/testbench', 'migrate:fresh', '--force'], { stdio: 'pipe' });
  execFileSync('php', ['tests/Fixtures/seed-ui-browser.php'], { stdio: 'pipe' });
  await page.setViewportSize({ width: 1227, height: 900 });
  await page.goto('/login-as/alice', { waitUntil: 'domcontentloaded' });
  await page.goto('/tasks', { waitUntil: 'domcontentloaded' });
  const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  await page.request.post('/workbench/playground', { form: {
    _token: token, board_key: 'tasks.board',
    'features[toolbar]': '1', 'features[details]': '1', 'features[view_switch]': '1',
    'views[default]': 'board', 'views[allowed_mode]': 'both', 'views[detail_surface]': 'drawer',
  } });
  await page.reload({ waitUntil: 'domcontentloaded' });
  const output = 'docs/redesign/k1k5';
  fs.mkdirSync(output, { recursive: true });
  async function show(surface = 'card') {
    const row = page.locator(`[data-kb-part="${surface === 'card' ? 'card' : 'list-row'}"][data-card="101"]`);
    await row.locator('[data-kb-part="card-menu-trigger"]').click();
    await row.locator('[data-kb-part="card-action-details"]').click();
    await expect(page.locator('[data-kb-part="details-drawer"]')).toBeVisible();
  }
  await show();
  const drawer = page.locator('[data-kb-part="details-drawer"]');
  const title = await drawer.locator('[data-kb-part="details-title"]').textContent();
  expect((await drawer.boundingBox()).width).toBeCloseTo(560, 0);
  await expect.poll(() => page.evaluate(() => document.body.style.overflow)).toBe('hidden');
  await drawer.locator('[data-kb-part="card-menu-trigger"]').focus();
  await page.keyboard.press('Tab');
  await expect(drawer.locator('[data-kb-part="details-close"]')).toBeFocused();
  await page.screenshot({ path: `${output}/drawer-light.jpg` });
  await page.keyboard.press('Escape');
  await expect(drawer).toHaveCount(0);
  await expect.poll(() => page.evaluate(() => document.body.style.overflow)).not.toBe('hidden');
  await page.locator('[data-kb-part="view-list"]').click();
  await show('row');
  await expect(drawer.locator('[data-kb-part="details-title"]')).toHaveText(title);
  await page.keyboard.press('Escape');
  await page.locator('.kb-wb-theme select').selectOption('dark');
  await page.locator('.kb-wb-theme button').click();
  await show('row');
  await page.screenshot({ path: `${output}/drawer-dark.jpg` });
  await page.keyboard.press('Escape');
  await page.locator('.kb-wb-locale select').selectOption('rtl');
  await show('row');
  await page.screenshot({ path: `${output}/drawer-rtl.jpg` });
  await page.setViewportSize({ width: 390, height: 844 });
  const box = await drawer.boundingBox();
  expect(box.x).toBeCloseTo(0, 0); expect(box.y).toBeCloseTo(0, 0);
  expect(box.width).toBeCloseTo(390, 0); expect(box.height).toBeCloseTo(844, 0);
  await expect(drawer.locator('[data-kb-part="details-close"]')).toBeVisible();
  await page.screenshot({ path: `${output}/drawer-mobile.jpg` });
  await page.keyboard.press('Escape');
  await expect(drawer).toHaveCount(0);
  for (const [name, theme, direction, width] of [
    ['light', 'light', 'ltr', 1227], ['dark', 'dark', 'ltr', 1227],
    ['rtl', 'light', 'rtl', 1227], ['mobile', 'light', 'rtl', 390],
  ]) {
    await page.setViewportSize({ width, height: 844 });
    await page.locator('.kb-wb-locale select').selectOption(direction);
    await page.locator('.kb-wb-theme select').selectOption(theme);
    await page.locator('.kb-wb-theme button').click();
    for (const view of ['board', 'list']) {
      await page.locator(`[data-kb-part="view-${view}"]`).click();
      await expect(page.locator('.kb')).toHaveAttribute('data-view', view);
      await page.screenshot({ path: `${output}/${view}-${name}.jpg` });
    }
  }
});
