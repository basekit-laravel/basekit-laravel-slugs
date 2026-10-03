<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\BasekitLaravelSlugsServiceProvider;
use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;

/*
 * These assertions turn the package rules in AGENTS.md into executable checks.
 * A package is consumed by other applications, so it must never reach for
 * application-only scaffolding, debugging helpers, or packages it does not
 * declare as a runtime dependency.
 */

arch('the package ships no debugging helpers')
    ->expect('BasekitLaravel\BasekitLaravelSlugs')
    ->not->toUse(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'var_export', 'die', 'exit']);

arch('the package never reaches into the host application')
    ->expect('BasekitLaravel\BasekitLaravelSlugs')
    ->not->toUse([
        'App',
        'Database\Factories',
        'Database\Seeders',
        'Tests\TestCase',
    ]);

arch('configuration comes from config files, never from the environment')
    ->expect('BasekitLaravel\BasekitLaravelSlugs')
    ->not->toUse(['env']);

arch('the package only depends on the Illuminate components it declares')
    ->expect('BasekitLaravel\BasekitLaravelSlugs')
    ->toOnlyUse([
        'BasekitLaravel',
        'BasekitLaravel\BasekitLaravelSlugs\Database\Factories',
        'Illuminate\Contracts\Support\ServiceProvider',
        'Illuminate\Database\Eloquent\Builder',
        'Illuminate\Database\Eloquent\Collection',
        'Illuminate\Database\Eloquent\Factories\HasFactory',
        'Illuminate\Database\Eloquent\Model',
        'Illuminate\Database\Eloquent\Relations\MorphMany',
        'Illuminate\Database\Eloquent\Relations\MorphTo',
        'Illuminate\Support\ServiceProvider',
        'Illuminate\Support\Str',
        // Global helpers provided by illuminate/support.
        'app',
        'config',
        'config_path',
        'database_path',
    ]);

arch('the service provider stays the only place that touches publishable paths')
    ->expect(BasekitLaravelSlugsServiceProvider::class)
    ->toOnlyUse([
        'BasekitLaravel\BasekitLaravelSlugs\SlugGenerator',
        'Illuminate\Support\ServiceProvider',
        'config',
        'config_path',
        'database_path',
    ]);

arch('the Slug model is a plain Eloquent model')
    ->expect(Slug::class)
    ->toExtend('Illuminate\Database\Eloquent\Model');
