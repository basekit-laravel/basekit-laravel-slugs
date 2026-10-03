<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models;

use BasekitLaravel\BasekitLaravelSlugs\HasSlugs;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSlugs;

    protected $table = 'pages';

    protected $guarded = [];
}
