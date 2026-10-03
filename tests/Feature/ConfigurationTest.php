<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use Illuminate\Support\Facades\DB;

test('the package configuration is merged with documented defaults', function (): void {
    $this->assertNull(config('basekit-laravel-slugs.default_locale'));
    $this->assertTrue(config('basekit-laravel-slugs.disambiguate'));
    $this->assertSame('-', config('basekit-laravel-slugs.generator.separator'));
    $this->assertFalse(config('basekit-laravel-slugs.generator.preserve_unicode'));
});

test('default_locale config controls the locale used implicitly', function (): void {
    config(['basekit-laravel-slugs.default_locale' => 'de']);

    $page = Page::create()->setSlug('ueber-uns');

    $this->assertSame('ueber-uns', $page->slug());
    $this->assertSame('de', DB::table('slugs')->sole()->locale);
});

test('default_locale config takes precedence over the application locale', function (): void {
    config(['basekit-laravel-slugs.default_locale' => 'de']);
    config(['app.locale' => 'es']);

    $page = Page::create()->setSlug('ueber-uns');

    $this->assertSame('de', DB::table('slugs')->sole()->locale);
});

test('preserve_unicode config controls how generated slugs handle accents', function (): void {
    config(['basekit-laravel-slugs.generator.preserve_unicode' => true]);

    $page = Page::create()->setSlugFrom('España');

    $this->assertSame('españa', $page->slug());
});

test('separator config controls how generated slugs join words', function (): void {
    config(['basekit-laravel-slugs.generator.separator' => '_']);

    $page = Page::create()->setSlugFrom('Hello World');

    $this->assertSame('hello_world', $page->slug());
});
