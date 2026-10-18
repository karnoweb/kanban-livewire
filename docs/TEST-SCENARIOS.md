# Test scenarios and release gate

## PHP suite

Run `composer test` for the complete PHPUnit suite. It covers the legacy provider path, builder/registry contracts, Eloquent multi-source scope, cursor binding, mixed-source identities, CRUD and domain hooks, WIP enforcement, idempotency, conflict/unknown outcomes, Realtime isolation, detail serialization, rendering, and Workbench behavior.

The scenario suite under `tests/Scenarios/` contains executable tests and workflow descriptions. KNB101–KNB150 form the enterprise acceptance set. Generate current evidence in `.phpunit.cache/enterprise-scenarios.json`; see [the testing guide](../README_Test.md) for commands and environment setup.

## Browser and database suites

Run the Playwright suite with `npm run e2e` against a dedicated Workbench database and isolated ports. Never point it at a developer or production database; the suite migrates its configured database from scratch. Browser coverage includes Board/List, viewport layouts, focus, keyboard and touch movement, RTL/LTR, light/dark themes, multiple boards, and Realtime.

`tests/Database/EnterprisePortableDatabaseTest.php` is opt-in for MariaDB/PostgreSQL and requires explicit `KANBAN_TEST_DB_DRIVER` and `KANBAN_TEST_DB_DATABASE`. The 10-column/100k-row query budget is measured by `tests/Performance/EnterpriseQueryPerformanceTest.php`; the machine-specific report is `.phpunit.cache/enterprise-benchmark.json`.

## Current release gate

Do not call a release ready unless Composer validation, asset build, full PHP suite, browser suite, and configured database suites pass. Record actual test and assertion counts, query/hydration counts, and unverified environments in the release report. Local test success does not certify a production deployment or real mobile hardware.
