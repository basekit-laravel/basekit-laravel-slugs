---
name: package-development
description: How to develop Laravel packages correctly — service providers, Composer, package discovery, config, migrations, routes, views, commands, translations, assets and testing.
license: MIT
compatibility: opencode
---

# Laravel Package Development

Use this skill when implementing or changing any part of this Laravel package
(`basekit-laravel-slugs`, PHP ^8.4|^8.5, Laravel ^13).

## Core principles

- A Laravel **package** is consumed inside other Laravel applications. Never assume
  application-only scaffolding (`app/`, auth, `.env`, local `config/app.php`, consumer views).
- The service provider is the package's entry point. It is auto-discovered through Composer's
  `extra.laravel.providers` when the consumer uses package discovery.
- Every public class, method, config key, and command is part of a contract that consumers
  depend on. Keep the public API small and stable.

## Service provider

- `register()`: merge config (`mergesConfigFrom`), bind container services — no side effects.
- `boot()`: load routes/views/translations, register commands, publish assets and config.
- Publishing tags should be namespaced, e.g. `basekit-laravel-slugs-config`, `basekit-laravel-slugs-migrations`,
  `basekit-laravel-slugs-views`, `basekit-laravel-slugs-lang`.
- Wrap command registration in `$this->app->runningInConsole()` where appropriate.

## Composer

- Runtime dependencies go in `require`; development tooling in `require-dev`.
- Respect the declared minimum PHP (`^8.4|^8.5`) and Laravel (`^13`).
- Prefer small, well-maintained dependencies; avoid a dependency when stdlib or Illuminate code
  suffices.
- Optional integrations belong in separate packages or service-provider checks, never in the
  hard dependency graph.

## Configuration

- Provide a default config file under `config/basekit-laravel-slugs.php`.
- Read values as `config('basekit-laravel-slugs.key', $default)` — consumers may override.
- `mergesConfigFrom` in the provider guarantees defaults exist before the consumer publishes.

## Migrations

- Ship migrations in `database/migrations/` that consumers publish. They run inside the
  consumer's connection — use portable column types and avoid engine-specific SQL.
- Prefix files with `YYYY_MM_DD_HHMMSS_` and keep ordering predictable.

## Routes, views, translations

- Load routes from `routes/web.php` / `routes/api.php` via `$this->loadRoutesFrom(...)`.
- Register Blade views with `$this->loadViewsFrom(..., 'basekit-laravel-slugs')`; reference them as
  `view('basekit-laravel-slugs::name')`.
- Register translations with `$this->loadTranslationsFrom(..., 'basekit-laravel-slugs')`; reference
  them as `trans('basekit-laravel-slugs::messages.key')`.

## Commands

- Define commands in `src/Commands/`, register them from the provider, and give them a stable
  signature and clear help text. Never hard-code paths or credentials.

## Assets

- Ship CSS/JS under `resources/`, publish with a dedicated tag, and document how consumers
  should register them.

## Testing

- Use the package `TestCase` (Orchestra Testbench) for anything touching the framework.
- Test the public behaviour a consumer would rely on; keep unit tests fast and dependency-free.
- Run `composer test` (and the configured analysis) before finishing and report real results.

## Verification checklist

1. Read the existing package structure and patterns first.
2. Implement the smallest correct change.
3. Add or update tests for meaningful behaviour.
4. Run the relevant checks and report what you actually ran.