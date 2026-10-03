<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models;

/**
 * A consumer model that opts out of automatic slug generation by returning null.
 */
class UnsluggedPost extends Post
{
    protected static function slugSourceAttribute(): ?string
    {
        return null;
    }
}
