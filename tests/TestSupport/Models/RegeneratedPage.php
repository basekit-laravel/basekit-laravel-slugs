<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A Page that exposes the trait's automatic slug generation so tests can run it
 * a second time and prove it never overwrites a slug that already exists.
 */
class RegeneratedPage extends Page
{
    public static function generateSlugOnCreate(Model $model): void
    {
        static::autoGenerateSlugOnCreate($model);
    }
}
