# Development and testing

This guide covers the package repository and its Workbench demonstration environment. For installing the package in a host application, see [README.md](README.md).

## Prerequisites

- PHP 8.3 or later, Composer, and SQLite support (`pdo_sqlite` and `sqlite3`).
- Node.js and npm for building frontend assets and running browser tests; CI uses Node.js 22.
- Microsoft Edge for the current Playwright browser configuration (`channel: msedge`).
- MariaDB or PostgreSQL only when running the optional database-portability suite.

The development lock file is resolved on PHP 8.4. CI resolves compatible dependencies separately on PHP 8.3; see [the CI workflow](.github/workflows/tests.yml).

## Repository setup

```bash
git clone https://github.com/karnoweb/kanban-livewire.git
cd kanban-livewire
composer install
npm ci
npm run build
```

`npm run build` produces `resources/dist/kanban.css` and `resources/dist/kanban.js`. Rebuild after editing package CSS or JavaScript. The Workbench serves these files directly.

## PHP tests

Run the complete default suite:

```bash
composer test
```

Run a particular suite or test:

```bash
php vendor/bin/phpunit --no-coverage --testsuite=Workbench
php vendor/bin/phpunit --no-coverage --testsuite=Security
php vendor/bin/phpunit --no-coverage --filter=CardDetailsProviderTest
```

The default suites include unit, feature, integration, security, performance, Workbench, PHP browser-contract, and documented scenario tests. Tests under `tests/Browser/` are part of PHPUnit; real browser automation lives in `e2e/` and is run separately with Playwright.

To produce the PHP report used by the scenario reporter:

```bash
php vendor/bin/phpunit --no-coverage --log-junit .phpunit.cache/enterprise-junit.xml
```

The opt-in database-portability tests are not included in the default PHPUnit suites.

## Workbench demo

The `workbench/` directory runs through Orchestra Testbench. It is not a separate Laravel application and its migrations belong only to the demo.

Create the local SQLite file if it does not exist, then migrate and seed it:

```bash
php -r "file_exists('workbench/database/workbench.sqlite') || touch('workbench/database/workbench.sqlite');"
php vendor/bin/testbench migrate --force
php vendor/bin/testbench db:seed --class="Workbench\Database\Seeders\KanbanDemoSeeder" --force
php vendor/bin/testbench serve --host=127.0.0.1 --port=8000
```

Open a demo login route first, then navigate to a board:

| Route | Identity |
| --- | --- |
| `/login-as/alice` | Alice, tenant-a |
| `/login-as/bob` | Bob, tenant-a |
| `/login-as/carol` | Carol, tenant-b |

| Page | Demonstration |
| --- | --- |
| `/tasks` | Tasks, board/list views, filtering, and capacity limits |
| `/crm` | Restricted transitions with a required transition note |
| `/tickets` | Ticket workflow and a read-only closed stage |
| `/orders` | Order workflow with host-owned approval rules |
| `/multi` | Independent boards on one page |
| `/models/tasks` | Eloquent task source |
| `/models/tickets` | Eloquent ticket source with different field mappings |
| `/models/classes` | Another independently registered model source |
| `/models/mixed` | Multiple sources with explicit stage mapping |
| `/models/compact` | Compact model presentation |

For optional realtime behavior, start Reverb in a second terminal and enable realtime for the relevant board:

```bash
php vendor/bin/testbench reverb:start
```

The demo login routes and fixtures are intended for local development, not deployment as application authentication.

## Real browser tests

Playwright starts its own Testbench and Reverb servers on the configured ports. Use a dedicated database and ports distinct from the interactive demo. Tests run `migrate:fresh` against `WORKBENCH_DB` and replace its data.

Install the configured browser if it is missing:

```bash
npx playwright install msedge
```

### PowerShell

```powershell
New-Item -ItemType Directory -Path .phpunit.cache -Force | Out-Null
$env:WORKBENCH_DB = Join-Path $PWD '.phpunit.cache/browser-tests.sqlite'
if (!(Test-Path -LiteralPath $env:WORKBENCH_DB)) {
    New-Item -ItemType File -Path $env:WORKBENCH_DB | Out-Null
}
$env:KANBAN_E2E_PORT = '18200'
$env:KANBAN_E2E_REVERB_PORT = '18201'
npm run e2e
```

### Bash

```bash
mkdir -p .phpunit.cache
export WORKBENCH_DB="$PWD/.phpunit.cache/browser-tests.sqlite"
touch "$WORKBENCH_DB"
export KANBAN_E2E_PORT=18200
export KANBAN_E2E_REVERB_PORT=18201
npm run e2e
```

Keep `WORKBENCH_DB` inside `.phpunit.cache/`; the UI suites validate this isolation. The servers inherit these environment variables. Do not reuse a server running with a different database on those ports.

Run a single browser scenario with the same environment:

```bash
npx playwright test e2e/drawer.spec.mjs
npx playwright test e2e/kanban.spec.mjs --grep "KNB-139"
```

Coverage includes drag/drop, touch and keyboard interaction, Board/List details, drawer focus and mobile sizing, themes, RTL/LTR, localized dates, permission/scope isolation, multiple boards, and realtime updates through Reverb.

## MariaDB and PostgreSQL

Use a dedicated, already-created database whose name starts with `kanban_agent_test_`. These tests create and remove fixture tables. The test adapter uses localhost, the default database port, and `root` for MySQL/MariaDB or `postgres` for PostgreSQL.

PowerShell example:

```powershell
$env:KANBAN_TEST_DB_DRIVER = 'mysql' # use pgsql for PostgreSQL
$env:KANBAN_TEST_DB_DATABASE = 'kanban_agent_test_local'
$env:KANBAN_TEST_DB_PASSWORD = '<test-database-password>'
php vendor/bin/phpunit --no-coverage tests/Database/EnterprisePortableDatabaseTest.php
```

For Bash, export the same three variables before running the command. Never supply an application database name or production credentials. CI provides disposable MariaDB and PostgreSQL services; see [the workflow](.github/workflows/tests.yml).

## Benchmarks and reports

The Eloquent performance test measures 10 columns with 10k and 100k rows, including query count and model hydration. The environment variable below enables writing its measurement report:

```powershell
$env:KANBAN_WRITE_BENCHMARK = '1'
php vendor/bin/phpunit --no-coverage tests/Performance/EnterpriseQueryPerformanceTest.php
```

In Bash, use `KANBAN_WRITE_BENCHMARK=1 php vendor/bin/phpunit --no-coverage tests/Performance/EnterpriseQueryPerformanceTest.php`.

After a complete PHP and browser run, regenerate the enterprise scenario evidence:

```bash
node scripts/report-enterprise-scenarios.mjs .phpunit.cache/enterprise-junit.xml .phpunit.cache/enterprise-browser.json
```

| Output | Contents |
| --- | --- |
| `.phpunit.cache/enterprise-junit.xml` | Executed PHP test results |
| `.phpunit.cache/enterprise-browser.json` | Executed Playwright results |
| `.phpunit.cache/enterprise-benchmark.json` | Machine-specific query/hydration measurements |
| `.phpunit.cache/realtime-latency.json` | Realtime latency samples from the browser suite |
| `.phpunit.cache/enterprise-scenarios.json` | Enterprise scenario-to-test mapping and results |
| `tests/Scenarios/` | Executable scenarios and their descriptions |
| `test-results/screenshots/` | Screenshots captured by UI scenarios |

Generated reports and screenshots are ignored by Git. Scenario reports are written to `.phpunit.cache/scenarios/`; the source descriptions in `tests/Scenarios/` remain unchanged. Measurements reflect the current environment, not fixed performance guarantees.

## Validation checklist

```bash
composer validate --strict --no-check-version
npm run build
composer test
git diff --check
```

Also run Playwright for interaction or layout changes and the database-portability suite when changing SQL or transactional behavior. Run the scenario reporter only after producing the corresponding test reports.

The [CI workflow](.github/workflows/tests.yml) runs PHP 8.3/8.4, browser tests on Windows, and MariaDB/PostgreSQL portability checks. The [release workflow](.github/workflows/release.yml) validates tagged package builds.

## Release validation

See [13.2.0 release notes](docs/releases/13.2.0.md) for the validation recorded for that release. Run the commands above for evidence from your current checkout.
