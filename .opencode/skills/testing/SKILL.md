---
name: testing
description: Package testing conventions for Pest and Orchestra Testbench — how to test a Laravel package from the consumer's perspective.
license: MIT
compatibility: opencode
---

# Package Testing

Use this skill when writing or running tests for this Laravel package
(`basekit-laravel-slugs`, PHP ^8.4|^8.5, Laravel ^13).

## Pest + Orchestra Testbench

- Tests that need the Laravel framework (container, config, routes, migrations, views) extend
  the package `TestCase`, which boots the package's service provider via
  `Orchestra\Testbench\TestCase`.
- Pure logic can be tested with plain Pest functions under `tests/Unit/` — no framework needed.
- The testing database is configured inside the package `TestCase`; keep tests isolated.

## Conventions

- `tests/Feature/` — behaviour tests from the consumer's perspective: call the public API,
  hit the routes, publish config, run toolbox migrations.
- `tests/Unit/` — fast, dependency-free unit tests for pure logic.
- Name tests by the behaviour they lock in (e.g. "it loads the config with defaults").
- Add a regression test alongside any bug fix that reproduces the reported issue.

## Running tests

```bash
composer test        # full Pest suite
```

Run the targeted subset while iterating, then the full suite before finishing. Report the
actual output — never claim passing tests you did not run.

## Verification expectations

Every meaningful behaviour change must include tests. When CI and static analysis are
configured for the package, run those too and report real results.