import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
test.beforeEach(() => {
  if (!process.env.WORKBENCH_DB?.replaceAll('\\', '/').includes('/.phpunit.cache/')) throw new Error('Isolated database required');
  execFileSync('php', ['vendor/bin/testbench', 'migrate:fresh', '--force'], { stdio: 'pipe' });
  execFileSync('php', ['tests/Fixtures/seed-ui-browser.php'], { stdio: 'pipe' });
});
test('desktop toolbar is one row in both directions; extra members open a bounded popup', async ({ page }) => {
  await page.setViewportSize({ width: 1227, height: 1244 });
  await page.goto('/login-as/alice');
  await page.goto('/tasks');
  for (const dir of ['ltr', 'rtl']) {
    await page.evaluate(dir => document.querySelector('.kb').setAttribute('dir', dir), dir);
    const start = await page.locator('[data-kb-part="toolbar-start"]').boundingBox();
    const end = await page.locator('[data-kb-part="toolbar-end"]').boundingBox();
    expect(Math.abs(start.y - end.y)).toBeLessThan(3);
    await expect(page.locator('[data-kb-part="toolbar-filter-stage"], [data-kb-part="toolbar-filter-priority"]')).toHaveCount(0);
    await expect(page.locator('[data-kb-part="toolbar-create-card"]')).toHaveAttribute('aria-label', 'Create card');
    expect(await page.locator('[data-kb-part="toolbar-create-card"]').innerText()).toBe('');
    expect(await page.locator('[data-kb-part="density-toggle"]').innerText()).toBe('');
    const members = page.locator('.kb-card[data-card="101"] [data-kb-part="card-assignees"]');
    await expect(members.locator(':scope > [data-kb-part="card-assignee"]')).toHaveCount(3);
    const more = members.locator('[data-kb-part="assignee-overflow"] > summary');
    await expect(more).toHaveText('+2');
    await more.click();
    const popup = members.locator('[data-kb-part="assignee-overflow-panel"]');
    await expect(popup).toContainText('Mina Rahimi');
    const box = await popup.boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(1227);
    fs.mkdirSync('test-results/screenshots/toolbar', { recursive: true });
    await page.screenshot({ path: `test-results/screenshots/toolbar/${dir}.jpg`, clip: { x: 0, y: 0, width: 1227, height: 780 } });
    await more.press('Escape');
    await expect(popup).not.toBeVisible();
  }
  await page.locator('[data-kb-part="filter-options"] > summary').click();
  await expect(page.locator('[data-kb-part="filter-stage"]')).toBeVisible();
  await expect(page.locator('[data-kb-part="filter-priority-high"]')).toBeVisible();
  await expect(page.locator('link[href*="fonts.googleapis.com/css2"]')).toHaveCount(1);
  expect(await page.locator('body').evaluate(el => getComputedStyle(el).fontFamily)).toContain('Inter');
  expect(await page.locator('body').evaluate(el => getComputedStyle(el).fontFamily)).toContain('Vazirmatn');
});
