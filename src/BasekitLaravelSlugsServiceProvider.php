<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs;

use Illuminate\Support\ServiceProvider;

final class BasekitLaravelSlugsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/basekit-laravel-slugs.php',
            'basekit-laravel-slugs',
        );

        // One generator per request, built from the package config, so consumers
        // can resolve the exact generator their slugs are generated with — for
        // validation in form requests, for example.
        $this->app->singleton(SlugGenerator::class, static fn (): SlugGenerator => new SlugGenerator(
            separator: (string) config('basekit-laravel-slugs.generator.separator', '-'),
            preserveUnicode: (bool) config('basekit-laravel-slugs.generator.preserve_unicode', false),
        ));
    }

    public function boot(): void
    {
        // Publishing is the only supported way to install the schema. The
        // migration is deliberately not also registered with loadMigrationsFrom():
        // publishing copies the file under a fresh timestamp, so the published
        // copy and the packaged copy would both be collected by the migrator and
        // the second one would fail with "table slugs already exists".
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'basekit-laravel-slugs-migrations');

        $this->publishes([
            __DIR__.'/../config/basekit-laravel-slugs.php' => config_path('basekit-laravel-slugs.php'),
        ], 'basekit-laravel-slugs-config');
    }
}
