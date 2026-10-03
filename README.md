# Basekit Laravel Slugs

Localized slugs for Eloquent models.

Store one slug per locale on any model using a shared polymorphic `slugs` table. Slugs can be set manually, generated from a value, or created automatically when a model is created.

```php
$page->setSlug('about');
$page->setSlug('acerca-de-nosotros', 'es');

$page->slug();      // 'about'
$page->slug('es');  // 'acerca-de-nosotros'
```

Generated slugs are normalized, transliterated to ASCII by default, and automatically suffixed when a duplicate exists.

The package only manages slugs. Routing, URLs and SEO remain part of your application.

## Requirements

- PHP 8.4 or 8.5
- Laravel 13

## Installation

Install the package:

```bash
composer require basekit-laravel/basekit-laravel-slugs
```

Publish the migration and run it:

```bash
php artisan vendor:publish --tag="basekit-laravel-slugs-migrations"
php artisan migrate
```

This creates the shared `slugs` table.

The default configuration works without publishing a config file. To change it:

```bash
php artisan vendor:publish --tag="basekit-laravel-slugs-config"
```

## Usage

Add the `HasSlugs` trait to any Eloquent model:

```php
use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSlugs;
}
```

### Setting and reading slugs

Slugs are stored per locale:

```php
$page = Page::create(['title' => 'About us']);

$page->setSlug('about');
$page->setSlug('acerca-de-nosotros', 'es');

$page->slug();      // 'about'
$page->slug('es');  // 'acerca-de-nosotros'
$page->slug('de');  // null

$page->hasSlug('es'); // true

$page->slugMap();
// ['en' => 'about', 'es' => 'acerca-de-nosotros']
```

When no locale is passed, the package uses the configured default locale, falling back to `config('app.locale')`.

`setSlug()` stores the value exactly as provided. It does not normalize or modify it.

### Generating a slug

Use `setSlugFrom()` when you want the package to generate the slug:

```php
$page->setSlugFrom($page->title);

// 'About us' -> 'about-us'
```

You can also specify a locale:

```php
$page->setSlugFrom($page->title, 'es');
```

By default, generated slugs:

- use `-` as the separator
- transliterate accented characters to ASCII
- add a numeric suffix when the slug already exists

For example:

```php
$generator->generate('España');

// 'espana'
```

### Duplicate slugs

Generated slugs are unique per model type and locale.

If the generated value already exists, a numeric suffix is added:

```php
Page::create(['title' => 'About us'])->slug(); // 'about-us'
Page::create(['title' => 'About us'])->slug(); // 'about-us-2'
Page::create(['title' => 'About us'])->slug(); // 'about-us-3'
```

Different model types may use the same slug:

```text
Page: about-us
Post: about-us
```

Different locales may also use the same value.

You can disable automatic disambiguation in the config:

```php
'disambiguate' => false,
```

A duplicate generated slug will then fail on the database unique constraint.

`setSlug()` never adds a suffix. It always attempts to store exactly the value you provide.

## Finding models by slug

Use `whereSlug()` to query a model:

```php
Page::whereSlug('about')->first();
Page::whereSlug('acerca-de-nosotros', 'es')->first();
```

For multiple slugs, use `whereSlugIn()`:

```php
Page::whereSlugIn(['about', 'pricing'])->get();
Page::whereSlugIn($slugs, 'es')->get();
```

The list is resolved in a single query. An empty list matches nothing.

## Automatic slug generation

The package can generate a slug automatically when a model is created.

Enable it in the config:

```php
'auto_slug' => [
    'enabled' => true,
    'attribute' => 'title',
],
```

Now models using `HasSlugs` receive a slug from that attribute:

```php
$page = Page::create([
    'title' => 'About us',
]);

$page->slug(); // 'about-us'
```

Automatic generation:

- runs only when the model is created
- does not replace an existing slug
- does not update the slug when the source attribute changes
- skips generation when the source attribute is empty
- only generates the default locale
- applies duplicate suffixes when needed

This keeps existing URLs stable when a model title changes.

### Per-model source attribute

The configured source attribute is global, but individual models can override it:

```php
class Product extends Model
{
    use HasSlugs;

    protected static function slugSourceAttribute(): ?string
    {
        return 'name';
    }
}
```

A model can opt out of automatic generation entirely:

```php
class Post extends Model
{
    use HasSlugs;

    protected static function slugSourceAttribute(): ?string
    {
        return null;
    }
}
```

You can still call `setSlugFrom()` manually on that model.

### Transactions when disambiguation is disabled

Automatic slug generation happens after the model has been inserted.

If disambiguation is disabled and the slug insert fails because of a duplicate, the model row has already been created.

Use a transaction when both writes should succeed or fail together:

```php
DB::transaction(
    fn () => Page::create(['title' => 'About us'])
);
```

## Using the generator directly

The slug generator can also be used independently of a model:

```php
use BasekitLaravel\BasekitLaravelSlugs\SlugGenerator;

$generator = app(SlugGenerator::class);

$generator->generate('España');  // 'espana'
$generator->isValid('about-us'); // true
```

## Configuration

The default configuration is:

```php
'default_locale' => null,

'disambiguate' => true,

'generator' => [
    'separator' => '-',
    'preserve_unicode' => false,
],

'auto_slug' => [
    'enabled' => false,
    'attribute' => 'title',
],
```

### Default locale

When `default_locale` is `null`, the package uses:

```php
config('app.locale')
```

You can set it explicitly:

```php
'default_locale' => 'en',
```

### Unicode slugs

By default, accented characters are transliterated:

```text
España -> espana
```

To keep Unicode characters:

```php
'generator' => [
    'preserve_unicode' => true,
],
```

This allows slugs such as:

```text
/españa
```

### Separator

Change the generated slug separator with:

```php
'generator' => [
    'separator' => '_',
],
```

For example:

```text
about_us
about_us_2
```

## Deleting models

Slug rows are deleted when their model is deleted.

Models using `SoftDeletes` keep their slugs while they are soft deleted. A force delete removes the associated slug rows.

## UUID and ULID primary keys

The published migration uses an unsigned big integer for `sluggable_id`, matching Laravel's default auto-incrementing primary key.

If your models use UUIDs or ULIDs, update the published migration before running it:

```php
$table->string('sluggable_type');
$table->string('sluggable_id', 26); // 36 for UUID, 26 for ULID
```

Then tell the model that string keys are supported by the slug storage:

```php
class Article extends Model
{
    use HasUlids;
    use HasSlugs;

    protected static function slugStorageSupportsStringKeys(): bool
    {
        return true;
    }
}
```

Only change the migration to string keys when your sluggable models actually use UUIDs or ULIDs.

## Morph maps

The package uses Eloquent's polymorphic type resolution, so existing morph maps are respected.

For example:

```php
Relation::enforceMorphMap([
    'page' => Page::class,
    'post' => Post::class,
]);
```

No additional package configuration is required.

## Testing

The package includes a factory for slug rows:

```php
use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;

$page = Page::create(['title' => 'About']);

Slug::factory()
    ->forSluggable($page)
    ->create(['slug' => 'about']);

Slug::factory()
    ->forSluggable($page, 'es')
    ->create(['slug' => 'acerca']);
```

Call `forSluggable()` once for each slug row you want to create.

## How it works

All slugs are stored in one polymorphic `slugs` table.

Each row contains:

- `sluggable_type`
- `sluggable_id`
- `locale`
- `slug`

The database enforces:

- one slug per model and locale
- one occurrence of a slug per model type and locale

This means two different model types can use the same slug, and the same slug can be used in different locales.

If your application requires slugs to be unique across all model types, adjust the indexes in the published migration.

## Contributing

Bug reports and pull requests are welcome.

Before submitting a change, run:

```bash
composer install
composer test
composer analyse
composer lint
```

Tests use in-memory SQLite by default.

To run the suite against another database, configure `TEST_DB_CONNECTION` as `sqlite`, `mysql` or `pgsql` together with the corresponding database connection environment variables.

Please report security issues privately. See [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE.md](LICENSE.md).
