# basekit-laravel-slugs

This package provides reusable slug infrastructure for Basekit Laravel:
deterministic slug generation and validation via `SlugGenerator`, a polymorphic
`slugs` table of one row per (model, locale) pair, and a `HasSlugs` trait that
gives any Eloquent model single-language or localized slugs. Slugs knows nothing
about Content, Pages, Blog, URLs, routing, SEO or publication.

It targets PHP ^8.4|^8.5 and Laravel ^13 and is distributed on Composer as
`basekit-laravel/basekit-laravel-slugs`. All package source code lives under the
`BasekitLaravel\BasekitLaravelSlugs` namespace.

## What this project is

This repository is a **Laravel package**, not a standalone Laravel application. The package is
consumed by other Laravel applications, so the code must never assume application-only
scaffolding such as `app/`, a booted authentication system, `.env` files, or a local
`config/app.php`. Everything the package needs must come from its own service provider,
configuration, and published resources.

Keep this file focused on **this package**. For framework-level knowledge, consult the official
Laravel documentation, Laravel Boost (if configured), or the available documentation MCP tools —
do not embed generic Laravel guidance here.

## Repository structure

- `src/` — package source code:
  - `src/HasSlugs.php` — the Eloquent integration trait (relation, read/write
    helpers, `whereSlug` scope, locale resolution, cleanup on delete).
  - `src/SlugGenerator.php` — deterministic slug generation, normalization and
    validation.
  - `src/Models/Slug.php` — an Eloquent model for one (model, locale) slug row.
  - `src/BasekitLaravelSlugsServiceProvider.php` — the service provider, registered
    automatically through Composer package discovery.
- `config/basekit-laravel-slugs.php` — `default_locale`, `disambiguate`,
  `generator` (`separator`, `preserve_unicode`) and `auto_slug` (`enabled`,
  `attribute`) options, merged via `mergeConfigFrom` and published with the
  `basekit-laravel-slugs-config` tag.
- `database/migrations/` — the package migration creating the `slugs` table.
  Published only, with
  `php artisan vendor:publish --tag="basekit-laravel-slugs-migrations"`. It is
  deliberately **not** also registered with `loadMigrationsFrom()`: publishing
  copies the file under a fresh timestamp, so the published and packaged copies
  would both be collected by the migrator and the second would fail with "table
  slugs already exists". Do not add `loadMigrationsFrom()` back.
- `database/factories/` — `SlugFactory`, autoloaded via the
  `BasekitLaravel\\BasekitLaravelSlugs\\Database\\Factories` PSR-4 entry in
  `composer.json`. Laravel 13 resolves factories through the autoloader, so
  `ServiceProvider::loadFactoriesFrom()` is both deprecated and unnecessary.
- `tests/` — Pest tests running against Orchestra Testbench (11.*). Unit tests
  under `tests/Unit/` are framework-free; framework tests under `tests/Feature/`
  use the package `TestCase`. Consumer-like models live in `tests/TestSupport/`.
- `.github/workflows/` — CI runs code style, static analysis, and the test suite
  on PHP 8.4 and 8.5 against SQLite, MySQL and PostgreSQL.

## Development commands
Install dependencies with `composer install`.

### Tests

```bash
composer test
```

By default the suite runs against in-memory SQLite. To run it against a real
server, set `TEST_DB_CONNECTION` (`sqlite`, `mysql`, `pgsql`) plus the matching
`TEST_DB_HOST`, `TEST_DB_PORT`, `TEST_DB_DATABASE`, `TEST_DB_USERNAME` and
`TEST_DB_PASSWORD` variables — this is what CI does, and it catches
driver-specific problems SQLite silently tolerates.

### Code style (Laravel Pint)

```bash
composer lint          # apply
composer lint:check    # verify only
```

### Static analysis (PHPStan / Larastan)

```bash
composer analyse
```

### Everything at once

```bash
composer quality
```

## Package development

When working on this package, treat it as any other piece of distributed software: the
public API you expose today is a contract your consumers rely on.

### Configuration

Configuration lives in `config/basekit-laravel-slugs.php`. It is merged with
`mergeConfigFrom` in the service provider, read with
`config('basekit-laravel-slugs.key', $default)`, and published with the
`basekit-laravel-slugs-config` tag. New options should have sensible defaults
(the simple single-locale case must need no configuration at all).
### Migrations

New tables and columns live in `database/migrations/`. Name files with the
`YYYY_MM_DD_HHMMSS_` prefix and follow standard Laravel migration ordering. Migrations are
published by consumers — make them forwards-compatible and avoid destructive irreversible
changes without a documented upgrade path.

The `slugs` table is shared by every consumer model through polymorphic columns
(`sluggable_type` / `sluggable_id`). Uniqueness is a schema decision: one slug
per (model, locale) and model-local slug uniqueness are both enforced by unique
indexes, and no cross-model constraint exists by default.

### Primary keys

The `slugs` table is created with `unsignedBigInteger('sluggable_id')` so that
`sluggable_type` / `sluggable_id` matches Laravel's default integer primary keys. Do
not change this to a string column: on PostgreSQL, comparing a `varchar`
`sluggable_id` against an `integer` model key raises `invalid input syntax for type
integer` for every model that uses the default key type.

Models keyed by UUID or ULID are supported by the consumer widening `sluggable_id`
to a string in the **published** migration and overriding
`slugStorageSupportsStringKeys()` to return true. Until a model does that,
`HasSlugs::setSlug()` throws a `LogicException` naming the column to change instead
of letting the driver fail with an opaque type error.

Do not try to detect the published column type at runtime to replace that opt-in.
Laravel reports column type names per driver (`integer` on MySQL and SQLite,
`int8` on PostgreSQL for the same `unsignedBigInteger` column), so a hardcoded list
of integer type names silently passes on one driver and rejects on another. Ask
the consumer instead — the column is theirs once published.

### Automatic slugs on create

`auto_slug.enabled` (default `false`) makes `HasSlugs` write a slug for the default
locale in a `created` listener, derived from `auto_slug.attribute`. It is skipped when
the option is off, the attribute is missing or blank, or the model already has a slug
for that locale — auto generation never overwrites a slug and never regenerates on
update.

`slugSourceAttribute()` resolves the source attribute per model and returns `null` to
opt a model out, so it is the seam a consumer overrides rather than the config value.

### Disambiguation

The top-level `disambiguate` option (default `true`) resolves a collision with a
numeric suffix via `SlugGenerator::withSuffix()` — the second `About us` becomes
`about-us-2`, skipping numbers already taken and honouring the configured separator.
Scoped per model type and locale, matching the unique index. A model never collides
with the slug it already owns. With it off, the collision surfaces as a
`UniqueConstraintViolationException`.

It is deliberately global rather than a per-model hook because it guards the unique
index: both entry points that generate a slug — `setSlugFrom()` and automatic
generation — resolve it through the same `uniqueSlugFor()`, so a consumer cannot end
up with one silently renaming URLs and the other failing. `setSlug()` stays verbatim
and never disambiguates.

Four constraints matter when changing automatic generation:

- The listener runs on `created`, not `creating`, because a slug row needs the model's
  primary key. A slug failure therefore happens after the model row is inserted, so a
  consumer that needs both rolled back must wrap the create in a transaction.
- The row is written by instantiating `Slug` and calling `sluggable()->associate()`,
  not `Slug::create()`: the polymorphic keys are not in `Slug::$fillable`, so mass
  assignment would silently drop them.
- All model-specific access happens through `static::` static dispatch, never a method
  call on a `Model`-typed parameter. That is what keeps Larastan clean; calling
  `$model->someTraitMethod()` from a boot listener does not type-check.
- The shared helpers are `protected static` rather than `private static` because they
  are reached from a `protected` listener; Larastan rejects private access there.

### Models and deletion

Slug cleanup runs on the `deleted` event, not `deleting`, so an aborted delete (a
sibling listener returning `false`, or a failing statement) leaves the model and its
slugs together rather than orphaning the model. Models using `SoftDeletes` keep their
slugs while trashed, so soft-deleted models continue to resolve; a force delete
removes them. The boot hook deletes through `Slug::query()` rather than the
`$model->slugs()` relation so the listener stays type-safe for any model.

### Testing

Write Pest tests under `tests/`. Prefer Tests\TestCase when the test needs the framework
container; keep pure logic tests under `tests/Unit/`. Verify behavior from the consumer's
perspective (create a consumer model using `HasSlugs` and assert against its public API)
rather than asserting implementation details.

`tests/Unit/ArchitectureTest.php` encodes package rules as executable tests: no `app/`
or `config/app.php` access, no `dd()`/`dump()`, no host `env()`, declared dependencies
only. Update its allowlist deliberately when a genuinely new dependency is added —
do not widen it to make a test pass.

## Compatibility

- Respect the Composer constraints in `composer.json`: PHP ^8.4|^8.5 and Laravel
  ^13. Do not introduce syntax, APIs, or dependencies that break the declared
  minimum versions.
- Classify dependencies correctly in `composer.json`: runtime needs go into `require`;
  development-only tooling goes into `require-dev`.
- Prefer requiring interfaces and small, well-maintained packages. Avoid adding a dependency
  where a few lines of stdlib or Illuminate code suffice.
- Package APIs are contracts. Avoid breaking changes; when they are unavoidable, follow the
  release workflow and document a migration path.
- New public classes, methods, config keys, and commands are public API — document them in the
  README and changelog.

## Agent rules

These rules apply to every AI agent working in this repository:

1. **Inspect first.** Before editing anything, read the relevant package structure, related
   classes, tests, configuration, Composer constraints, and existing patterns.
2. **Search before creating.** Before creating a new class, component, or config option, look
   for an existing equivalent.
3. **Prefer existing patterns.** Follow the package's existing architecture and conventions.
4. **Minimal changes.** Implement the smallest correct change that satisfies the request.
5. **Write tests.** Every meaningful behavior change should come with tests.
6. **Run the relevant checks.** Actually run the tests/analysis documented above, and the
   targeted subset when a full run is impractical.
7. **Format your changes.** Run the configured formatter on the files you touched.
8. **Inspect your diff.** Review what you changed before reporting completion.
9. **Do not modify generated or vendor files.** Files under `vendor/`, published resources that
   are not yours, and generated artifacts must never be hand-edited.
10. **Report honestly.** Never claim "tests pass" or "analysis is clean" unless you actually ran
    the commands and they succeeded.