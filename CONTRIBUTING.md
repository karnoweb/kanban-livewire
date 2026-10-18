# Contributing

Install PHP 8.3+ and Composer, then:

```bash
composer install
vendor/bin/phpunit
```

Node is used for building assets and running browser tests; it is not required to serve the prebuilt CSS. Do not add a migration under `src`. Do not weaken the default `deny` transition policy.

For full environment setup, PHP/browser/database tests and generated reports, see [README_Test.md](README_Test.md).
