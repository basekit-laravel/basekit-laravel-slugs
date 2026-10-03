---
name: code-review
description: Package review checklist — architecture, public API, compatibility, Composer, Laravel integration, database, security, testing, documentation and performance.
license: MIT
compatibility: opencode
---

# Package Code Review

Use this skill when reviewing changes to this Laravel package
(`basekit-laravel-slugs`, PHP ^8.4|^8.5, Laravel ^13).

## Checklist

- **Architecture**: consistent with the package structure and existing patterns; minimal scope.
- **Public API**: no unnecessary new API surface; public behaviour is documented and stable.
- **Compatibility**: within supported PHP `^8.4|^8.5` and Laravel `^13`;
  no use of host-app scaffolding; no breaking behaviour change.
- **Composer**: dependencies justified and classified (`require` vs `require-dev`).
- **Laravel integration**: package discovery and provider registration correct; config/views/
  translations/routes namespaced and publishable.
- **Database**: migrations portable and forward compatible; indexes, constraints, and
  factories in sync.
- **Security**: input validated; authorization enforced; no secrets or sensitive data leaked
  in logs, storage, or commits.
- **Testing**: behaviour tests added or updated; tests exercise the public API and run cleanly.
- **Documentation**: README and changelog reflect the change; upgrade notes for breakage.
- **Performance**: no unnecessary per-request or boot-time work; no N+1 or missing indexes.

## Reporting

- Inspect the actual diff and surrounding context before commenting.
- Report findings in severity order (blocking → major → minor → nit) with a location and a
  concrete suggestion for each.
- Distinguish verified facts from suggestions and never claim checks you did not run.