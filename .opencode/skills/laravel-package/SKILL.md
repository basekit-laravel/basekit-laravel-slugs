---
name: laravel-package
description: Package-specific Laravel conventions — how to write portable Laravel package code that works from consumers' applications, contrasting package and application development.
license: MIT
compatibility: opencode
---

# Laravel Package Conventions

Use this skill any time you write Laravel code in this repository
(`basekit-laravel-slugs`, PHP ^8.4|^8.5, Laravel ^13).

## Laravel application vs Laravel package

| Concern | Laravel application | Laravel package |
| --- | --- | --- |
| Root | application root | consumer's application root |
| Providers | `App\Providers` | the package's own service provider |
| Config | `config/` overridden by consumers | shipped defaults merged by the provider |
| Routes | registered in the app | loaded from `routes/` by the provider |
| Views | `resources/views/layouts/...` | namespaced via `loadViewsFrom` |
| Migrations | run locally | published and run by consumers |
| `base_path()` | the app | the **consumer's** app — do not rely on it |
| `.env` | read directly | never assume environment variables exist |

## Rules for package code

- Do not reference `app/`, `database/` (of the host app), `artisan` internals, or the host's
  auth/session stacks unless the package explicitly depends on them.
- Resolve paths with the package's own `__DIR__` context when publishing or loading resources.
- Read configuration through the container/config with defaults, never assume the key exists.
- Prefer `illuminate/contracts` and small, stable packages over heavyweight framework APIs.
- Defer heavy work until it is actually used — lazy singletons, deferred providers, commands
  registered via `runningInConsole()`.

## When unsure

Look at `composer.json` (dependencies and PSR-4), the service provider, and existing package
tests to confirm what this package may rely on. Generic Laravel framework knowledge comes from
the official documentation or Laravel Boost / documentation MCP tools — not from assumptions.