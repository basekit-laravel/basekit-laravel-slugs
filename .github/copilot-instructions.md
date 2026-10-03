# GitHub Copilot Instructions

This repository is a Laravel package: `basekit-laravel-slugs` — reusable slug
infrastructure for Basekit Laravel: deterministic slug generation and validation
(`SlugGenerator`), a polymorphic `slugs` table of one row per (model, locale)
pair, and a `HasSlugs` trait that gives any Eloquent model single-language or
localized slugs.

It targets PHP ^8.4|^8.5 and Laravel ^13 and is distributed on Composer as
`basekit-laravel/basekit-laravel-slugs`. The canonical agent instructions live in `AGENTS.md` — if you
can read that file, prefer it over these instructions. This file exists so Copilot surfaces
that only read `copilot-instructions.md` (for example code review) still follow project rules.

## Working in this repository

- This is a **Laravel package**, not a standalone Laravel application. Never assume
  application-only scaffolding such as `app/`, authentication, `.env`, or a local `config/app.php`.
- Respect the Composer constraints in `composer.json` (PHP ^8.4|^8.5, Laravel ^13)
  and classify runtime vs development dependencies correctly (`require` vs `require-dev`).
- Package APIs (public classes, methods, config keys, commands) are contracts — avoid breaking
  changes and document the public behavior.
- Prefer existing package patterns; implement the smallest correct change.
- Require tests for meaningful behavior changes and document their status honestly.

## Verification commands (use only those configured in composer.json)

- Tests: `composer test`
- Code style: `composer lint` (Laravel Pint); `composer lint:check` to verify only
- Static analysis: `composer analyse` (PHPStan / Larastan)
- Refactoring rules: `composer refactor:check` (Rector)

`composer test` runs against in-memory SQLite by default. To reproduce CI, set
`TEST_DB_CONNECTION` (`sqlite`, `mysql`, `pgsql`) plus `TEST_DB_HOST`,
`TEST_DB_PORT`, `TEST_DB_DATABASE`, `TEST_DB_USERNAME` and `TEST_DB_PASSWORD`.

## Things that are easy to get wrong here

- The `slugs` migration is **published only**. There is deliberately no
  `loadMigrationsFrom()` call in the service provider — adding it back makes
  consumers fail with "table slugs already exists".
- Laravel 13 resolves model factories through the autoloader, so
  `ServiceProvider::loadFactoriesFrom()` must not be used.
- Slug rows are removed on `deleted`, not `deleting`, so an aborted delete does
  not orphan the model's slugs. Soft deletes keep their slugs.
- `disambiguate` is a global option, not per model: `setSlugFrom()` and automatic
  generation both append a numeric suffix on a collision, while `setSlug()` stays
  verbatim. Both generation paths share `uniqueSlugFor()`; keep it that way.
- `sluggable_id` is an unsigned big integer. UUID/ULID-keyed models need the
  published migration edited to widen `sluggable_id` to a string **and** an override
  of `slugStorageSupportsStringKeys()` on the model. Do not infer the column type
  at runtime: Laravel reports `integer` on MySQL/SQLite but `int8` on PostgreSQL for
  the same column.

## Repository guardrails

- Never modify files under `vendor/` or generated/published artifacts.
- Inspect the relevant code first, search for existing equivalents before creating new
  components, and report verification results truthfully.
