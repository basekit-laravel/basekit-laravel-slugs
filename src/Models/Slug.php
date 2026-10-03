<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Models;

use BasekitLaravel\BasekitLaravelSlugs\Database\Factories\SlugFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single slug row, owned by exactly one Eloquent model (e.g. a Page or
 * a Post) through a polymorphic relation. Each row pairs a locale with the
 * slug a model uses in that locale, so a model with only one slug stores a
 * single row under the package's default locale.
 *
 * @property string $locale
 * @property string $slug
 * @property Model $sluggable
 *
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> all($columns = ['*'])
 */
class Slug extends Model
{
    /** @use HasFactory<SlugFactory> */
    use HasFactory;

    protected $table = 'slugs';

    protected $fillable = [
        'locale',
        'slug',
    ];

    /**
     * The model this slug belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The package ships its own factory rather than relying on the
     * application's Database\Factories namespace.
     */
    protected static function newFactory(): SlugFactory
    {
        return SlugFactory::new();
    }
}
