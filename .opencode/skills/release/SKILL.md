---
name: release
description: Release preparation for this Laravel package — semantic versioning, changelog, README alignment, migration notes, and verification before tagging.
license: MIT
compatibility: opencode
---

# Package Release

Use this skill when preparing a release for this Laravel package
(`basekit-laravel-slugs`, PHP ^8.4|^8.5, Laravel ^13).

## Before you release

1. **Semantic versioning**: breaking public API changes → `major`; new backward-compatible
   features → `minor`; bug fixes and internal changes → `patch`. Public API includes classes,
   methods, config keys, endpoints, and commands.
2. **Changelog**: record all user-visible changes. Include migration notes for breaking
   changes and new-feature usage notes.
3. **README**: verify the README matches the released behaviour (installation, configuration,
   usage, commands). Fix anything that drifted.
4. **Compatibility**: confirm the package still supports the declared PHP (`^8.4|^8.5`) and
   Laravel (`^13`) constraints.
5. **Verification**: run the full suite and static analysis and record the real results —
   `composer test`, and any configured analysis/formatting commands from `composer.json`.

## Release sequence

- Update the changelog and README in the same commit as the version bump.
- Bump the version in `composer.json`; keep other manifests in sync.
- Create a semver tag for the version matching the Composer version.
- Do not publish to Packagist automatically — a maintainer does that after the tag.

## Guardrails

- Never tag a release that has not passed the documented verification.
- Never introduce unreviewed breaking changes into a patch release.