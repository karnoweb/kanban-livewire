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
  if (path !== '/tasks') {
    await page.goto(path);
  }
  await page.waitForSelector('.kb');
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
  await drop(page, id, 'doing');
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
