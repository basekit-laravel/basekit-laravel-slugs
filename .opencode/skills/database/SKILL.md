---
name: database
description: Database conventions for this Laravel package — portable migrations, schema design, factories, seeders and database tests.
license: MIT
compatibility: opencode
---

# Package Database Conventions

Use this skill when working with the package database layer
(`basekit-laravel-slugs`, Laravel ^13).

## Migrations that consumers publish

- Ship migrations in `database/migrations/`; consumers publish and run them in their own
  applications and database engines.
- Name files with the `YYYY_MM_DD_HHMMSS_` prefix and keep them ordered.
- Use portable column types and avoid engine-specific SQL or database-specific features.
- Make migrations reversible (`down`) where practical, or document why they are not.

## Schema design

- Add indexes on columns used in `where`, `orderBy`, and joins; keep names consistent.
- Prefer explicit length/decimal/precision declarations over engine defaults.
- Use unsigned/signed integers deliberately and keep foreign-key constraints explicit.
- Avoid destructive changes (dropping tables/columns) without a documented upgrade path.

## Factories and seeders

- Define factories in `database/factories/` (or as Laravel 11+ `Factory` classes) matching the
  package models.
- Seeders use the configured connection and are safe to run more than once.

## Database tests

- Cover migration up/down behaviour.
- Cover boundary behaviour: nullable columns, unique constraints, cascades, ordering.
- Run the database test subset locally against the package's test connection before finishing.