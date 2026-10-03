<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs;

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use LogicException;

/**
 * Adds localized slugs to any Eloquent model.
 *
 * A model using this trait owns zero or more slugs, one per locale. The
 * single-language case is just a model with slug rows under one locale:
 *
 *     $page->setSlug('about');
 *     $page->slug(); // "about"
 *
 * Localized models add more rows:
 *
 *     $page->setSlug('sobre-nosotros', 'es');
 *     $page->slug('es'); // "sobre-nosotros"
 *     Page::whereSlug('sobre-nosotros', 'es')->firstOrFail();
 *
 * @property-read Collection<int, Slug> $slugs
 *
 * @mixin Model
 */
trait HasSlugs
{
    /**
     * All slug rows owned by this model.
     *
     * @return MorphMany<Slug, $this>
     */
    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    /**
     * The slug for a locale, or null when the locale has no slug. Falls back
     * to the package's default locale when none is given; there is no other
     * fallback chain.
     */
    public function slug(?string $locale = null): ?string
    {
        return $this->slugRecord($locale)?->slug;
    }

    /**
     * Whether this model has a slug for the given locale.
     */
    public function hasSlug(?string $locale = null): bool
    {
        return $this->slugRecord($locale) !== null;
    }

    /**
     * Every slug this model owns, keyed by locale.
     *
     * @return array<string, string>
     */
    public function slugMap(): array
    {
        /** @var array<string, string> $map */
        $map = $this->slugs->pluck('slug', 'locale')->all();

        return $map;
    }

    /**
     * Set (or replace) the slug for a locale. Passing null removes the slug
     * for that locale.
     *
     * The given value is stored verbatim and never disambiguated, so one that
     * another model already uses is rejected by the unique index. Generate
     * first if you are not working with a slug already; see setSlugFrom().
     */
    public function setSlug(?string $slug, ?string $locale = null): static
    {
        self::guardKeyStorage($this);

        $locale ??= $this->defaultLocale();

        if ($slug === null) {
            $this->slugs()->where('locale', $locale)->delete();
        } elseif (($record = $this->slugRecord($locale)) !== null) {
            $record->update(['slug' => $slug]);
        } else {
            $this->slugs()->create(['locale' => $locale, 'slug' => $slug]);
        }

        unset($this->relations['slugs']);

        return $this;
    }

    /**
     * Generate a slug from an arbitrary value and store it. Convenient for
     * deriving a slug from a title or name without requiring this package to
     * know which attribute holds it:
     *
     *     $page->setSlugFrom($page->title);
     *
     * Generated slugs are disambiguated exactly like automatic ones, so a value
     * already used by another model of the same type in that locale becomes
     * "about-us-2" unless the global disambiguate option is off.
     */
    public function setSlugFrom(string $value, ?string $locale = null): static
    {
        self::guardKeyStorage($this);

        $locale ??= $this->defaultLocale();

        $candidate = $this->slugGenerator()->generate($value);

        return $this->setSlug(static::uniqueSlugFor($this, $locale, $candidate), $locale);
    }

    /**
     * Scope to models that have the given slug in a locale.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWhereSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $locale ??= $this->defaultLocale();

        return $query->whereHas('slugs', function (Builder $slugs) use ($slug, $locale): void {
            $slugs->where('locale', $locale)->where('slug', $slug);
        });
    }

    /**
     * Scope to models that have any of the given slugs in a locale.
     *
     * One query resolves a whole batch, which is what sitemap generation and
     * bulk URL resolution need. An empty list matches nothing.
     *
     * @param  Builder<$this>  $query
     * @param  iterable<string>  $slugs
     * @return Builder<$this>
     */
    public function scopeWhereSlugIn(Builder $query, iterable $slugs, ?string $locale = null): Builder
    {
        $wanted = [...$slugs];

        if ($wanted === []) {
            return $query->whereRaw('1 = 0');
        }

        $locale ??= $this->defaultLocale();

        return $query->whereHas('slugs', function (Builder $relation) use ($wanted, $locale): void {
            $relation->where('locale', $locale)->whereIn('slug', $wanted);
        });
    }

    /**
     * Resolve the locale rules: an explicit argument wins, then the package's
     * default_locale config, then the application's default locale.
     */
    protected function defaultLocale(): string
    {
        return static::slugDefaultLocale();
    }

    /**
     * The configured default locale, shared by the instance and static paths.
     */
    protected static function slugDefaultLocale(): string
    {
        return (string) (config('basekit-laravel-slugs.default_locale') ?? config('app.locale', 'en'));
    }

    /**
     * Build the generator used by setSlugFrom(), honouring the package config.
     */
    protected function slugGenerator(): SlugGenerator
    {
        return app(SlugGenerator::class);
    }

    /**
     * Whether this model's primary key can be stored in the slugs table as it
     * is published.
     *
     * The published migration types sluggable_id as an unsigned big integer, so
     * UUID and ULID models must widen that column to a string and override
     * this to return true. Laravel reports column type names per driver
     * ("integer" on MySQL, "int8" on PostgreSQL), so the package asks instead
     * of guessing from the schema.
     */
    protected static function slugStorageSupportsStringKeys(): bool
    {
        return false;
    }

    /**
     * The slugs table stores sluggable_id as an unsigned bigint, so a model can
     * only own slugs when it exists and its key fits the published column.
     * Failing here turns an opaque driver-level type error into an actionable
     * message.
     */
    protected static function guardKeyStorage(Model $model): void
    {
        $key = $model->getKey();

        if ($key === null) {
            throw new LogicException(
                'Cannot write a slug for an unsaved ['.$model::class.']. Save the model before setting its slug.'
            );
        }

        if (is_int($key) || (is_string($key) && ctype_digit($key))) {
            return;
        }

        if (static::slugStorageSupportsStringKeys()) {
            return;
        }

        throw new LogicException(
            'Cannot write a slug for ['.$model::class.'] because its primary key ['.$key.'] is not an integer, '
            .'while the slugs table stores sluggable_id as an unsigned bigint. To use UUID or ULID keys, widen '
            .'sluggable_id to a string in the published migration and override slugStorageSupportsStringKeys() '
            .'on ['.$model::class.'] to return true.'
        );
    }

    /**
     * Loaded slug rows keep per-locale reads free of extra queries; otherwise
     * a single indexed query fetches the row.
     */
    protected function slugRecord(?string $locale = null): ?Slug
    {
        $locale ??= $this->defaultLocale();

        $record = $this->relationLoaded('slugs')
            ? $this->slugs->firstWhere('locale', $locale)
            : $this->slugs()->where('locale', $locale)->first();

        return $record instanceof Slug ? $record : null;
    }

    /**
     * Generate and store a slug for a newly created model.
     *
     * Controlled by the auto_slug config block: it runs only when
     * auto_slug.enabled is true, derives the slug from
     * slugSourceAttribute(), and writes it for the default locale.
     *
     * It deliberately does nothing when the source attribute is missing or
     * empty, or when the model already has a slug for that locale, so a slug
     * set explicitly is never overwritten.
     */
    protected static function autoGenerateSlugOnCreate(Model $model): void
    {
        if (! config('basekit-laravel-slugs.auto_slug.enabled')) {
            return;
        }

        $attribute = static::slugSourceAttribute();

        if ($attribute === null || $attribute === '') {
            return;
        }

        $value = $model->getAttribute($attribute);

        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $locale = static::slugDefaultLocale();

        $alreadyOwned = Slug::query()
            ->where('sluggable_type', $model->getMorphClass())
            ->where('sluggable_id', $model->getKey())
            ->where('locale', $locale)
            ->exists();

        if ($alreadyOwned) {
            return;
        }

        self::guardKeyStorage($model);

        $generator = app(SlugGenerator::class);

        $slug = new Slug;
        $slug->locale = $locale;
        $slug->slug = static::uniqueSlugFor($model, $locale, $generator->generate($value));
        $slug->sluggable()->associate($model);
        $slug->save();
    }

    /**
     * The attribute a slug is generated from on create, or null to opt this
     * model out of automatic slugs entirely.
     *
     * Defaults to the auto_slug.attribute config value so the common case needs
     * no per-model code.
     */
    protected static function slugSourceAttribute(): ?string
    {
        $attribute = config('basekit-laravel-slugs.auto_slug.attribute');

        return is_string($attribute) && $attribute !== '' ? $attribute : null;
    }

    /**
     * Resolve a generated slug to one that no other model of the same type is
     * already using in this locale, adding a numeric suffix when the global
     * disambiguate option is on.
     *
     * Shared by setSlugFrom() and automatic generation so both entry points
     * resolve a collision identically. Slugs the model already owns are not
     * considered taken, so re-generating the same value keeps the slug stable.
     *
     * Uniqueness is scoped to the model type because that is what the unique
     * index enforces; different model types may reuse a slug.
     */
    protected static function uniqueSlugFor(Model $model, string $locale, string $candidate): string
    {
        if (! config('basekit-laravel-slugs.disambiguate')) {
            return $candidate;
        }

        $slug = $candidate;
        $suffix = 2;

        while (static::slugIsTakenBy($model, $locale, $slug)) {
            $slug = app(SlugGenerator::class)->withSuffix($candidate, $suffix);
            $suffix++;
        }

        return $slug;
    }

    /**
     * Whether a slug is already used by a different model of the same type in
     * this locale.
     */
    protected static function slugIsTakenBy(Model $model, string $locale, string $slug): bool
    {
        return Slug::query()
            ->where('sluggable_type', $model->getMorphClass())
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('sluggable_id', '!=', $model->getKey())
            ->exists();
    }

    /**
     * Owned slug rows are cleaned up with the model. Soft-deleted models keep
     * their slugs so they keep resolving while trashed; rows are removed on a
     * force delete.
     *
     * Cleanup runs on "deleted" rather than "deleting" so an aborted delete —
     * a sibling listener returning false, or a failing statement — leaves the
     * model and its slugs together instead of orphaning the model.
     */
    public static function bootHasSlugs(): void
    {
        // Runs on "created" rather than "creating" because a slug row can only
        // be written once the model has a primary key.
        static::created(static function (Model $model): void {
            static::autoGenerateSlugOnCreate($model);
        });

        static::deleted(static function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            Slug::query()
                ->where('sluggable_type', $model->getMorphClass())
                ->where('sluggable_id', $model->getKey())
                ->delete();
        });
    }
}
