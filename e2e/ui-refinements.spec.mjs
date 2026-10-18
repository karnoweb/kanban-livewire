import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const output = 'docs/redesign/refinements';
test.beforeEach(() => {
  if (!process.env.WORKBENCH_DB?.replaceAll('\\', '/').includes('/.phpunit.cache/')) throw new Error('Use an isolated UI test database');
  execFileSync('php', ['vendor/bin/testbench', 'migrate:fresh', '--force'], { stdio: 'pipe' });
  execFileSync('php', ['tests/Fixtures/seed-ui-browser.php'], { stdio: 'pipe' });
  fs.mkdirSync(output, { recursive: true });
});
async function open(page) {
  await page.route('**/workbench/ui-fixture-cover.svg', route => route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300"><rect width="600" height="300" fill="#e8efff"/><rect x="50" y="50" width="150" height="200" rx="16" fill="#2563eb"/><rect x="225" y="50" width="150" height="140" rx="16" fill="#93b4ff"/><rect x="400" y="50" width="150" height="90" rx="16" fill="#bfd3ff"/></svg>' }));
  await page.goto('/login-as/alice');
  await page.goto('/tasks');
  await expect(page.locator('.kb')).toBeVisible();
}
async function details(page) {
  await expect.poll(() => page.evaluate(() => {
    const root = document.querySelector('.kb');
    return Boolean(root && window.Livewire?.find(root.getAttribute('wire:id')));
  })).toBe(true);
  const item = page.locator('.kb-card[data-card="101"]');
  await item.locator('[data-kb-part="card-menu-trigger"]').click();
  await item.locator('[data-kb-part="card-action-details"]').click();
  await expect(page.locator('[data-kb-part="details-modal"]')).toBeVisible();
}

test('refinements: fixed stage footer, form actions, stage select and direct density toggle', async ({ page }) => {
  await page.setViewportSize({ width: 1157, height: 900 });
  await open(page);
  await expect(page.locator('[data-kb-part="stage-collapse"], [data-kb-part="appearance-options"], [data-kb-part="theme-switch"], [data-kb-part="dir-switch"]')).toHaveCount(0);
  const stage = page.locator('.kb-stage[data-stage="todo"]');
  const add = stage.locator('[data-kb-part="stage-add-card"]');
  const before = await add.boundingBox();
  await stage.locator('.kb-stage-scroll').evaluate(el => { el.scrollTop = el.scrollHeight; });
  expect((await add.boundingBox()).y).toBeCloseTo(before.y, 0);
  await add.click();
  const inline = stage.locator('.kb-inline-create');
  await expect(inline).toBeVisible();
  expect((await inline.locator('button[type=submit]').boundingBox()).x).toBeGreaterThan((await inline.locator('button[type=button]').boundingBox()).x);
  await inline.locator('input').fill('Inline regression card');
  await inline.locator('input').press('Enter');
  await expect(stage.locator('.kb-card', { hasText: 'Inline regression card' })).toHaveCount(1);
  await page.locator('[data-kb-part="toolbar-create-card"]').click();
  const form = page.locator('[data-kb-part="card-form"]');
  await expect(form.locator('select[wire\\:model="cardForm.stageId"]')).toBeVisible();
  await form.locator('input').first().fill('Selected stage regression');
  await form.locator('select').first().selectOption('doing');
  expect((await form.locator('button[type=submit]').boundingBox()).x).toBeGreaterThan((await form.getByRole('button', { name: 'Cancel', exact: true }).boundingBox()).x);
  await page.screenshot({ path: `${output}/create-card.jpg` });
  await form.locator('button[type=submit]').click();
  await expect(page.locator('.kb-stage[data-stage="doing"] .kb-card', { hasText: 'Selected stage regression' })).toBeVisible();
  await page.locator('[data-kb-part="density-toggle"]').click();
  await expect(page.locator('.kb')).toHaveAttribute('data-density', 'compact');
  await page.locator('[data-kb-part="density-toggle"]').click();
  await expect(page.locator('.kb')).toHaveAttribute('data-density', 'comfortable');
});

test('refinements: first image cover, full details, host theme and archived deletion', async ({ page }) => {
  await open(page);
  await expect(page.locator('.kb-card[data-card="101"] [data-kb-part="card-cover"]')).toBeVisible();
  await page.locator('[data-kb-part="density-toggle"]').click();
  await expect(page.locator('.kb')).toHaveAttribute('data-density', 'compact');
  await details(page);
  const modal = page.locator('[data-kb-part="details-modal"]');
  await expect(modal).toContainText('Review requirements');
  await expect(modal).toContainText('Ready for review.');
  await expect(modal.locator('[data-kb-part="details-attachments"]')).toContainText('architecture-rfc-v2.pdf');
  await expect(modal.locator('[data-kb-part="details-attachments"] a').first()).toBeVisible();
  await expect(modal.locator('.kb-assignee').first()).toHaveAttribute('title', /Alice/);
  await page.screenshot({ path: `${output}/details-light.jpg` });
  await modal.getByRole('button', { name: 'Close', exact: true }).click();
  await page.locator('.kb-wb-theme select').selectOption('dark');
  await page.locator('.kb-wb-theme button').click();
  await expect(page.locator('.kb')).toHaveAttribute('data-theme', 'dark');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await details(page);
  await page.screenshot({ path: `${output}/details-dark.jpg` });
  await modal.getByRole('button', { name: 'Close', exact: true }).click();
  const card = page.locator('.kb-card[data-card="101"]');
  await card.locator('[data-kb-part="card-menu-trigger"]').click();
  await card.locator('[data-kb-part="card-action-archive"]').click();
  await expect(card).toHaveCount(0);
  await page.locator('.kb-overflow-menu > summary').click();
  await page.locator('[data-kb-part="toolbar-archive"]').click();
  const archive = page.locator('[data-kb-part="archive-browser"]');
  await expect(archive.locator('[data-kb-part="archive-delete"]')).toBeVisible();
  await page.screenshot({ path: `${output}/archive.jpg` });
  page.once('dialog', dialog => dialog.accept());
  await archive.locator('[data-kb-part="archive-delete"]').click();
  await expect(archive.locator('[data-kb-part="archive-item"]')).toHaveCount(0);
});

test('refinements: filters stay in the viewport on desktop, mobile and RTL', async ({ page }) => {
  await open(page);
  for (const width of [1157, 390]) {
    await page.setViewportSize({ width, height: 844 });
    await page.locator('[data-kb-part="filter-options"] > summary').click();
    const panel = page.locator('[data-kb-part="filter-options"] .kb-toolbar-options-body');
    await expect(panel).toBeVisible();
    await expect.poll(async () => (await panel.boundingBox()).x).toBeGreaterThanOrEqual(0);
    const box = await panel.boundingBox();
    expect(box.x + box.width).toBeLessThanOrEqual(width);
    await page.screenshot({ path: `${output}/filters-${width}.jpg` });
    await page.locator('[data-kb-part="filter-options"] > summary').click();
  }
  await page.goto('/models/tasks');
  await expect(page.locator('.kb')).toHaveAttribute('dir', 'rtl');
  await page.locator('[data-kb-part="filter-options"] > summary').click();
  const panel = page.locator('[data-kb-part="filter-options"] .kb-toolbar-options-body');
  await expect(panel).toBeVisible();
  await expect.poll(async () => (await panel.boundingBox()).x).toBeGreaterThanOrEqual(0);
  const box = await panel.boundingBox();
  expect(box.x + box.width).toBeLessThanOrEqual(390);
  await page.screenshot({ path: `${output}/filters-rtl-mobile.jpg` });
  await page.goto('/tasks');
  await details(page);
  const modal = page.locator('[data-kb-part="details-modal"]');
  const modalBox = await modal.boundingBox();
  expect(modalBox.y + modalBox.height).toBeLessThanOrEqual(844);
  await page.screenshot({ path: `${output}/details-mobile.jpg` });
});
