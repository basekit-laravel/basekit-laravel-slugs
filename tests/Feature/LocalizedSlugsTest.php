<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use Illuminate\Support\Facades\DB;

test('a model can hold different slugs per locale', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');
    $page->setSlug('rolunk', 'hu');

    $this->assertSame('about', $page->slug('en'));
    $this->assertSame('sobre-nosotros', $page->slug('es'));
    $this->assertSame('rolunk', $page->slug('hu'));
});

test('the same slug may be used across different locales', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('about', 'es');

    $this->assertSame('about', $page->slug('en'));
    $this->assertSame('about', $page->slug('es'));
    $this->assertSame(2, DB::table('slugs')->count());
});

test('locales without a translation simply have no slug', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    $this->assertNull($page->slug('hu'));
    $this->assertSame(2, DB::table('slugs')->count());
});

test('removing one locale leaves the others untouched', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    $page->setSlug(null, 'es');

    $this->assertNull($page->slug('es'));
    $this->assertSame('about', $page->slug('en'));
});

test('slug resolves the locale from the application when none is given', function (): void {
    $page = Page::create();
    $page->setSlug('about');

    config(['app.locale' => 'es']);

    $this->assertNull($page->slug());

    $page->setSlug('sobre-nosotros');

    $this->assertSame('sobre-nosotros', $page->slug());

    config(['app.locale' => 'en']);

    $this->assertSame('about', $page->slug());
});

test('locales are plain strings and accept region suffixes', function (): void {
    $page = Page::create();
    $page->setSlug('my-first-post');
    $page->setSlug('mi-primer-articulo', 'es-ES');

    $this->assertSame('my-first-post', $page->slug('en'));
    $this->assertSame('mi-primer-articulo', $page->slug('es-ES'));
});

test('localized lookups return exactly the matching model', function (): void {
    $english = Page::create()->setSlug('hello');
    $spanish = Page::create()->setSlug('hello', 'es');

    $this->assertTrue(Page::whereSlug('hello')->firstOrFail()->is($english));
    $this->assertTrue(Page::whereSlug('hello', 'es')->firstOrFail()->is($spanish));
    $this->assertSame(1, Page::whereSlug('hello')->count());
    $this->assertSame(1, Page::whereSlug('hello', 'es')->count());
});
