import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

function resetDatabase() {
  execFileSync('php', ['vendor/bin/testbench', 'migrate:fresh', '--force'], { stdio: 'pipe' });
}

async function open(browser, name, path = '/tasks') {
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto(`/login-as/${name}`);
  await page.goto(path);
  await page.waitForSelector('.kb');
  // Realtime is opt-in in the demos; enable it explicitly in this test session.
  const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const keys = await page.locator('.kb').evaluateAll(roots => roots.map(root => root.dataset.board));
  for (const key of keys) {
    const form = { _token: token, board_key: key };
    for (const feature of ['toolbar', 'search', 'filters', 'view_switch', 'theme_switch', 'direction_switch', 'density_switch', 'create_card', 'update_card', 'create_stage', 'update_stage', 'archive_card', 'archive_stage', 'restore_card', 'drag_drop', 'reorder_cards', 'reorder_stages', 'bulk', 'wip', 'details', 'virtualization', 'realtime', 'custom_actions']) {
      form[`features[${feature}]`] = '1';
    }
    // PHP form parsing requires bracket notation for nested fields.
    form['views[default]'] = 'board'; form['views[allowed_mode]'] = 'both';
    const response = await page.request.post('/workbench/playground', { form, maxRedirects: 0 });
    expect(response.status()).toBe(302);
  }
  await page.reload();
  return { context, page };
}

const card = (page, id, stage) => page.locator(`[data-stage="${stage}"] [data-card="${id}"]`);

async function drop(page, id, toStage) {
  await page.dragAndDrop(`[data-card="${id}"]`, `[data-stage="${toStage}"] .kb-stage-scroll`);
}

async function waitRealtime(page, count = 1) {
  await expect.poll(() => page.evaluate(() => window.KbRealtime.size())).toBe(count);
  await expect.poll(() => page.evaluate(() => {
    const p = window.Echo && window.Echo.connector.pusher;
    return p ? p.connection.state : 'none';
  })).toBe('connected');
  // subscription handshake finished
  await page.waitForTimeout(400);
}

test.beforeEach(() => {
  resetDatabase();
});

test('KB-001/KB-011 render and drag move persists on the server', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice');
  await expect(page.locator('.kb-stage')).toHaveCount(3);
  await expect(card(page, '101', 'todo')).toBeVisible();

  await drop(page, '101', 'doing');
  await expect(card(page, '101', 'doing')).toBeVisible();

  await page.reload();
  await expect(card(page, '101', 'doing')).toBeVisible();
  await context.close();
});

test('KB-022/KB-023 strict WIP limit is enforced by the server', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice');
  await drop(page, '101', 'doing');
  await expect(card(page, '101', 'doing')).toBeVisible();
  await drop(page, '102', 'doing');
  await expect(card(page, '102', 'doing')).toBeVisible();
  await drop(page, '103', 'doing');
  await expect(card(page, '103', 'doing')).toBeVisible();

  await page.locator('[data-stage="todo"] button', { hasText: 'Create card' }).click();
  await page.locator('[data-stage="todo"] .kb-inline-create input').fill('fourth');
  await page.locator('[data-stage="todo"] .kb-inline-create button[type=submit]').click();
  await expect(page.locator('[data-stage="todo"] .kb-card h3', { hasText: 'fourth' })).toBeVisible();

  const fourth = page.locator('[data-stage="todo"] .kb-card', { hasText: 'fourth' });
  const id = await fourth.getAttribute('data-card');
  // A full column blocks native drop; the keyboard/menu path must also be rejected by the server.
  await fourth.locator('summary').click();
  await fourth.locator('[data-target-stage="doing"]').click();
  await expect(page.locator('.kb-live')).toHaveText('wip_limit');
  await expect(card(page, id, 'todo')).toBeVisible();
  await context.close();
});

test('KB-034 two users: move is delivered through Reverb without reload', async ({ browser }) => {
  const alice = await open(browser, 'alice');
  const bob = await open(browser, 'bob');
  await waitRealtime(alice.page);
  await waitRealtime(bob.page);

  await drop(alice.page, '101', 'doing');
  await expect(card(alice.page, '101', 'doing')).toBeVisible();
  await expect(card(bob.page, '101', 'doing')).toBeVisible({ timeout: 5000 });
  await alice.context.close();
  await bob.context.close();
});

test('KB-038/KB-044 tenant and board isolation', async ({ browser }) => {
  const alice = await open(browser, 'alice', '/multi');
  const carol = await open(browser, 'carol');
  await waitRealtime(alice.page, 2);
  await waitRealtime(carol.page);

  await expect(carol.page.locator('[data-card="101"] h3')).toContainText('tenant-b');

  const boards = alice.page.locator('.kb[data-board="tasks.board"]');
  await boards.locator('[data-card="101"]').dragTo(boards.locator('[data-stage="doing"] .kb-stage-scroll'));
  const taskCard = boards.locator('[data-stage="doing"] [data-card="101"]');
  await expect(taskCard).toBeVisible();

  const orders = alice.page.locator('.kb[data-board="orders.board"]');
  await expect(orders.locator('[data-stage="review"] [data-card="101"]')).toBeVisible();

  await alice.page.waitForTimeout(1500);
  await expect(card(carol.page, '101', 'todo')).toBeVisible();
  await expect(carol.page.locator('[data-stage="doing"] [data-card="101"]')).toHaveCount(0);

  const denied = await carol.page.evaluate(() => new Promise((resolve) => {
    const hash = document.querySelector('.kb').getAttribute('data-kb-channel');
    const foreign = hash.replace(/^private-kanban\.[0-9a-f]+\./, 'kanban.' + '0'.repeat(64) + '.');
    const channel = window.Echo.connector.pusher.subscribe('private-' + foreign.replace(/^private-/, ''));
    channel.bind('pusher:subscription_error', () => resolve('denied'));
    channel.bind('pusher:subscription_succeeded', () => resolve('allowed'));
    setTimeout(() => resolve('timeout'), 5000);
  }));
  expect(denied).toBe('denied');

  await alice.context.close();
  await carol.context.close();
});

test('KB-037 offline, missed event, reconnect refetch', async ({ browser }) => {
  const alice = await open(browser, 'alice');
  const bob = await open(browser, 'bob');
  await waitRealtime(alice.page);
  await waitRealtime(bob.page);

  await bob.page.evaluate(() => window.Echo.connector.pusher.disconnect());
  await expect(bob.page.locator('.kb')).toHaveAttribute('data-connection', 'offline');

  await drop(alice.page, '102', 'done');
  await expect(card(alice.page, '102', 'done')).toBeVisible();
  await bob.page.waitForTimeout(800);
  await expect(card(bob.page, '102', 'todo')).toBeVisible();

  await bob.page.evaluate(() => window.Echo.connector.pusher.connect());
  await expect(card(bob.page, '102', 'done')).toBeVisible({ timeout: 8000 });
  await expect(bob.page.locator('.kb')).toHaveAttribute('data-connection', 'online');
  await alice.context.close();
  await bob.context.close();
});

test('KB-043 listeners are detached when the board is removed', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice', '/multi');
  await waitRealtime(page, 2);
  await page.evaluate(() => document.querySelector('.kb[data-board="orders.board"]').remove());
  await expect.poll(() => page.evaluate(() => window.KbRealtime.size())).toBe(1);
  await page.evaluate(() => document.querySelector('.kb').remove());
  await expect.poll(() => page.evaluate(() => window.KbRealtime.size())).toBe(0);
  await context.close();
});

test('KB-039 XSS in a title is rendered as text', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice');
  const payload = '<img src=x onerror="window.__xss=1">';
  await page.locator('[data-stage="todo"] button', { hasText: 'Create card' }).click();
  await page.locator('[data-stage="todo"] .kb-inline-create input').fill(payload);
  await page.locator('[data-stage="todo"] .kb-inline-create button[type=submit]').click();
  await expect(page.locator('[data-stage="todo"] .kb-card h3', { hasText: payload })).toBeVisible();
  expect(await page.evaluate(() => window.__xss)).toBeUndefined();
  expect(await page.locator('[data-stage="todo"] .kb-card h3 img').count()).toBe(0);
  await context.close();
});

test('KNB-139 model board, list, compact, sources and aggregate metrics', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice', '/models/mixed');
  await expect(page.locator('.kb-card')).toHaveCount(9);
  await page.locator('[data-kb-part="filter-options"] > summary').click();
  await expect(page.locator('[data-kb-part="filter-source"]')).toBeVisible();
  await page.locator('[data-kb-part="filter-source"]').selectOption('tasks');
  await expect(page.locator('.kb-card')).toHaveCount(3);
  await expect(page.locator('[data-kb-part="stage-metric"]', { hasText: 'tasks.amount: 60' })).toBeVisible();
  await page.locator('[data-kb-part="view-list"]').click();
  await expect(page.locator('[data-kb-part="list-row"]')).toHaveCount(3);
  await page.goto('/models/compact');
  await expect(page.locator('.kb')).toHaveAttribute('data-view', 'compact');
  await expect(page.locator('.kb-card')).toHaveCount(3);
  await context.close();
});

test('KNB-143 collapse and keyboard move persist on the model provider', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice', '/models/tasks');
  const todo = page.locator('[data-kb-part="stage"][data-stage="todo"]');
  await todo.locator('[data-kb-part="stage-collapse"]').click();
  await expect(todo.locator('[data-kb-part="stage-body"]')).toBeHidden();
  await page.reload();
  await expect(todo.locator('[data-kb-part="stage-body"]')).toBeHidden();
  await todo.locator('[data-kb-part="stage-collapse"]').click();
  await expect(todo.locator('[data-kb-part="stage-body"]')).toBeVisible();
  const item = todo.locator('.kb-card').first();
  const id = await item.getAttribute('data-card');
  await item.locator('summary').press('Enter');
  await expect(item.locator('[data-target-stage="doing"]')).toBeVisible();
  await item.locator('[data-target-stage="doing"]').press('Enter');
  await expect(card(page, id, 'doing')).toBeVisible();
  const moved = card(page, id, 'doing');
  await moved.locator('summary').press('Enter');
  await moved.locator('[data-kb-part="card-action-edit"]').press('Enter');
  await page.locator('[data-kb-part="card-form-title"] input').fill('Edited browser task');
  await page.locator('[data-kb-part="card-form"] button[type="submit"]').click();
  await expect(moved).toContainText('Edited browser task');
  await page.reload();
  await expect(card(page, id, 'doing')).toContainText('Edited browser task');
  await context.close();
});

test('KNB window navigation keeps every loaded card reachable and preserves scroll', async ({ browser }) => {
  execFileSync('php', ['tests/Fixtures/seed-enterprise-browser.php'], { stdio: 'pipe' });
  const { context, page } = await open(browser, 'alice', '/models/tasks');
  const stage = page.locator('[data-kb-part="stage"][data-stage="todo"]');
  const body = stage.locator('[data-kb-part="stage-body"]');
  await body.evaluate(el => { el.scrollTop = 200; });
  await page.locator('[data-kb-part="appearance-options"] > summary').click();
  await page.locator('[data-kb-part="theme-dark"]').click();
  await expect(page.locator('.kb')).toHaveAttribute('data-theme', 'dark');
  await expect.poll(() => body.evaluate(el => el.scrollTop)).toBe(200);
  await stage.locator('[data-kb-part="load-more"]').press('Enter');
  await expect(stage.locator('.kb-card')).toHaveCount(50);
  await stage.locator('[data-kb-part="load-more"]').press('Enter');
  await expect(stage.locator('[data-kb-part="virtual-list"]')).toHaveAttribute('data-loaded', '75');
  await expect(stage.locator('.kb-card')).toHaveCount(50);
  await stage.locator('[data-kb-part="window-next"]').click();
  await expect(stage).toContainText('Window card 75');
  await stage.locator('[data-kb-part="window-previous"]').click();
  await expect(stage).toContainText('tenant-a tasks 1');
  await context.close();
});

test('KNB-144 touch handle moves a model card; mobile RTL and theme remain usable', async ({ browser }) => {
  const context = await browser.newContext({ hasTouch: true, isMobile: true, viewport: { width: 900, height: 800 } });
  const page = await context.newPage();
  await page.goto('/login-as/alice');
  await page.goto('/models/tasks');
  const item = page.locator('[data-stage="todo"] .kb-card').first();
  const id = await item.getAttribute('data-card');
  const handle = item.locator('[data-kb-part="card-drag-handle"]');
  const target = page.locator('[data-kb-part="stage"][data-stage="doing"]');
  await target.scrollIntoViewIfNeeded();
  const box = await target.boundingBox();
  const point = { x: box.x + box.width / 2, y: box.y + Math.min(box.height / 2, 150) };
  const start = await handle.boundingBox();
  const input = await context.newCDPSession(page);
  await input.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: start.x + start.width / 2, y: start.y + start.height / 2 }] });
  await input.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: point.x, y: point.y }] });
  await input.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
  await expect(card(page, id, 'doing')).toBeVisible();
  await page.setViewportSize({ width: 390, height: 800 });
  await expect(page.locator('.kb')).toHaveAttribute('dir', 'rtl');
  await page.locator('[data-kb-part="appearance-options"] > summary').click();
  await page.locator('[data-kb-part="theme-dark"]').click();
  await expect(page.locator('.kb')).toHaveAttribute('data-theme', 'dark');
  await context.close();
});

test('realtime latency: alice move to bob DOM (p95 recorded)', async ({ browser }) => {
  const alice = await open(browser, 'alice');
  const bob = await open(browser, 'bob');
  await waitRealtime(alice.page);
  await waitRealtime(bob.page);

  await alice.page.evaluate(() => {
    document.addEventListener('drop', () => { window.__t0 = Date.now(); }, true);
  });

  const samples = [];
  let stage = 'todo';

  for (let i = 0; i < 20; i++) {
    const target = stage === 'todo' ? 'done' : 'todo';
    await bob.page.evaluate(({ target }) => {
      window.__t1 = null;
      const selector = `[data-stage="${target}"] [data-card="101"]`;
      const observer = new MutationObserver(() => {
        if (window.__t1 === null && document.querySelector(selector)) {
          window.__t1 = Date.now();
          observer.disconnect();
        }
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }, { target });

    await drop(alice.page, '101', target);
    await expect(card(alice.page, '101', target)).toBeVisible();
    await expect(card(bob.page, '101', target)).toBeVisible({ timeout: 5000 });
    const t0 = await alice.page.evaluate(() => window.__t0);
    const seen = await bob.page.evaluate(() => window.__t1);
    samples.push(seen - t0);
    stage = target;
  }

  samples.sort((a, b) => a - b);
  const p95 = samples[Math.ceil(samples.length * 0.95) - 1];
  fs.mkdirSync('docs', { recursive: true });
  fs.writeFileSync('docs/E2E-REALTIME-LATENCY.json', JSON.stringify({
    measuredAt: new Date().toISOString(),
    browser: 'msedge',
    samples: samples.length,
    minMs: samples[0],
    medianMs: samples[Math.floor(samples.length / 2)],
    p95Ms: p95,
    maxMs: samples[samples.length - 1],
    note: 'alice drop event to bob DOM observation (upper bound, includes polling), local Reverb, SQLite',
  }, null, 2));
  expect(p95).toBeLessThan(2500);
  await alice.context.close();
  await bob.context.close();
});


test('design: host tokens inherit and modal keyboard focus remains contained', async ({ browser }) => {
  const { context, page } = await open(browser, 'alice');
  if (process.env.KANBAN_DESIGN_CAPTURE) {
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.screenshot({ path: '.phpunit.cache/design-after-desktop.png' });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: '.phpunit.cache/design-after-mobile.png' });
    await page.setViewportSize({ width: 1440, height: 1000 });
  }
  await page.locator('[data-kb-part="appearance-options"] > summary').click();
  await page.locator('[data-kb-part="theme-system"]').click();
  await expect(page.locator('.kb')).not.toHaveAttribute('data-theme');
  await page.addStyleTag({ content: 'body { --color-primary: oklch(45% .2 270); --color-primary-content: oklch(99% 0 0); --color-base-100: oklch(95% .03 210); --radius-box: 18px; }' });
  const colors = await page.evaluate(() => {
    const probe = document.createElement('span');
    probe.style.backgroundColor = 'var(--color-primary)'; document.body.append(probe);
    const expected = getComputedStyle(probe).backgroundColor; probe.remove();
    return { expected, actual: getComputedStyle(document.querySelector('[data-kb-part="toolbar-create-card"]')).backgroundColor,
      radius: getComputedStyle(document.querySelector('.kb-card')).borderRadius };
  });
  await expect.poll(() => page.locator('[data-kb-part="toolbar-create-card"]').evaluate(el => getComputedStyle(el).backgroundColor)).toBe(colors.expected);
  expect(colors.radius).toBe('18px');
  await page.locator('[data-kb-part="appearance-options"] > summary').click();
  const trigger = page.locator('[data-kb-part="toolbar-create-card"]');
  await trigger.click();
  const dialog = page.locator('[data-kb-part="card-form"]');
  await expect(dialog).toHaveAttribute('aria-modal', 'true');
  await expect(dialog).toHaveAccessibleName('Create card');
  await expect(dialog.locator('input').first()).toBeFocused();
  await expect(page.locator('.kb-toolbar')).toHaveAttribute('inert', '');
  await dialog.locator('button').last().focus();
  await page.keyboard.press('Tab');
  await expect(dialog.locator('input').first()).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(dialog).toHaveCount(0);
  await expect(trigger).toBeFocused();
  await expect(page.locator('.kb-toolbar')).not.toHaveAttribute('inert');
  await context.close();
});
