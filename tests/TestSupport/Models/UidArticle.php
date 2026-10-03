<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models;

use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A ULID-keyed consumer model that has widened sluggable_id to a string in the
 * published migration and opted in accordingly.
 */
class UidArticle extends Model
{
    use HasSlugs;
    use HasUlids;

    protected $table = 'articles';

    protected $guarded = [];

    /**
     * @see HasSlugs::slugStorageSupportsStringKeys()
     */
    protected static function slugStorageSupportsStringKeys(): bool
    {
        return true;
    }
}
