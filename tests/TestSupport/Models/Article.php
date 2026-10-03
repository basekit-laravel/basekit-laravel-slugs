<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models;

use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A consumer model keyed by a ULID rather than an auto-incrementing integer.
 * The published slugs table stores sluggable_id as an unsigned bigint, so this
 * model cannot own slugs until that column is widened.
 */
class Article extends Model
{
    use HasSlugs;
    use HasUlids;

    protected $table = 'articles';

    protected $guarded = [];
}
