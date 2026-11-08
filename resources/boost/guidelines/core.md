## Basekit Laravel Slugs (`basekit-laravel/basekit-laravel-slugs`)

Provides localized, polymorphic slug storage and deterministic slug generation for Eloquent models. Deliberately does not handle routing, URL building, or SEO tags.

- **Layer:** Foundation (see DECISIONS.md section 8)
- Requirements: PHP `^8.4|^8.5`, Laravel `^13` (`illuminate/contracts`, `illuminate/database`, `illuminate/support`)

### Key classes and entry points
- `HasSlugs`: trait to add localized slugs, scopes (`whereSlug`, `whereSlugIn`), and slug management methods to Eloquent models.
- `Slug`: Eloquent model representing a polymorphic `slugs` table record (`locale`, `slug`, `sluggable`).
- `SlugGenerator`: service for deterministic slug generation, transliteration, normalization, and validation (`isValid`).
- `BasekitLaravelSlugsServiceProvider`: registers singleton `SlugGenerator`, config, and publishable migration and config.

### Usage
```php
use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;

class Page extends Model { use HasSlugs; }

$page->setSlug('about');
$page->setSlug('acerca-de-nosotros', 'es');

$page->slug();     // 'about'
$page->slug('es'); // 'acerca-de-nosotros'
Page::whereSlug('acerca-de-nosotros', 'es')->first();
```

### Commands and configuration
- Config file: `config/basekit-laravel-slugs.php`.
- Key options: `default_locale` (`null`), `disambiguate` (`true`), `generator.separator` (`'-'`), `generator.preserve_unicode` (`false`), `auto_slug.enabled` (`false`), `auto_slug.attribute` (`'title'`).
- Publish tags: `basekit-laravel-slugs-migrations`, `basekit-laravel-slugs-config`.

### Conventions
- Always store and query slugs with locale awareness (`locale` is required per row).
- Slugs are unique per `(sluggable_type, locale, slug)`.
- Models must be persisted to database (`$model->exists === true`) before setting slugs.
- Keep the package database-portable (no engine-specific SQL; portable Schema builder).
- Dependencies point to lower layers only; no admin UI in this package.
- Test with at least two locales (for example `en`, `es`).

### Do not
- Do not call `setSlug()` on an unsaved model; the model must have an ID first.
- Do not assume `setSlug()` disambiguates duplicates; only `setSlugFrom()` or auto-slugging disambiguate with suffixes.
- Do not use UUID/ULID models without manually altering `sluggable_id` column to a string in the published migration.
- Do not auto-load the migration; consumers must publish and run `basekit-laravel-slugs-migrations`.
