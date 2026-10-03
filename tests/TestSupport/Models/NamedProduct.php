<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models;

/**
 * A consumer model that derives its slug from "name" while the package config
 * names "title", proving a model can override the source attribute.
 */
class NamedProduct extends Product
{
    protected static function slugSourceAttribute(): ?string
    {
        return 'name';
    }
}
