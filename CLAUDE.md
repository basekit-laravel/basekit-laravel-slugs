# Claude Code Instructions

This repository contains a Laravel package (`basekit-laravel-slugs`) that provides
reusable slug infrastructure for Basekit Laravel — deterministic slug generation
and validation (`SlugGenerator`), a polymorphic `slugs` table of one row per
(model, locale) pair, and a `HasSlugs` trait that gives any Eloquent model
single-language or localized slugs. The canonical project instructions live in
`AGENTS.md` and always take precedence over anything in this file.

@AGENTS.md

## Claude Code specifics

- The canonical instructions for this package are in `AGENTS.md` — if it is present, read it
  before starting work and follow it.
- Follow the "Agent rules" section of `AGENTS.md` in every session.
- Run the package's configured verification commands (tests, formatter, static analysis)
  exactly as documented in `AGENTS.md` and report the real results.
- Never modify files under `vendor/` or generated/published artifacts.
- Prefer the package's existing patterns over introducing new ones; keep changes minimal.