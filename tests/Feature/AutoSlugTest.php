<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\NamedProduct;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Product;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\RegeneratedPage;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\UnsluggedPost;

beforeEach(function (): void {
    config()->set('basekit-laravel-slugs.auto_slug.enabled', true);
    config()->set('basekit-laravel-slugs.auto_slug.attribute', 'title');
    config()->set('basekit-laravel-slugs.disambiguate', true);
});

test('creating a model generates a slug from the configured attribute', function (): void {
    $page = Page::create(['title' => 'About us']);

    expect($page->slug())->toBe('about-us')
        ->and(Slug::query()->count())->toBe(1);
});

test('the generated slug honours the default locale', function (): void {
    config()->set('basekit-laravel-slugs.default_locale', 'es');

    $page = Page::create(['title' => 'Sobre nosotros']);

    expect($page->slug('es'))->toBe('sobre-nosotros')
        ->and($page->slug('en'))->toBeNull();
});

test('nothing is generated while auto_slug is disabled', function (): void {
    config()->set('basekit-laravel-slugs.auto_slug.enabled', false);

    $page = Page::create(['title' => 'About us']);

    expect($page->slug())->toBeNull()
        ->and(Slug::query()->count())->toBe(0);
});

test('auto generation is off by default', function (): void {
    config()->set('basekit-laravel-slugs.auto_slug', require __DIR__.'/../../config/basekit-laravel-slugs.php');

    $page = Page::create(['title' => 'About us']);

    expect($page->slug())->toBeNull();
});

test('the source attribute is configurable', function (): void {
    config()->set('basekit-laravel-slugs.auto_slug.attribute', 'name');

    $product = Product::create(['name' => 'Blue Widget']);

    expect($product->slug())->toBe('blue-widget');
});

test('a model without the source attribute is left without a slug', function (): void {
    $page = new Page;
    $page->save();

    expect($page->slug())->toBeNull()
        ->and(Slug::query()->count())->toBe(0);
});

test('an empty source value is skipped rather than generating a fallback slug', function (): void {
    $page = Page::create(['title' => '   ']);

    expect($page->slug())->toBeNull()
        ->and(Slug::query()->count())->toBe(0);
});

test('auto generation never overwrites a slug that already exists', function (): void {
    $page = Page::create(['title' => 'About us']);
    $page->setSlug('custom');

    // A listener that stored a slug before ours ran must keep it.
    $other = Page::create(['title' => 'Pricing']);

    expect($page->slug())->toBe('custom')
        ->and($other->slug())->toBe('pricing');
});

test('running generation again leaves an existing slug alone', function (): void {
    $page = RegeneratedPage::create(['title' => 'About us']);
    $page->setSlug('custom');

    RegeneratedPage::generateSlugOnCreate($page);

    expect($page->fresh()->slug())->toBe('custom')
        ->and(Slug::query()->where('slug', 'custom')->count())->toBe(1);
});

test('auto generation runs once and later attribute changes keep the slug stable', function (): void {
    $page = Page::create(['title' => 'About us']);

    $page->update(['title' => 'Completely different']);

    expect($page->fresh()->slug())->toBe('about-us')
        ->and(Slug::query()->count())->toBe(1);
});

test('auto generation applies to every model using the trait', function (): void {
    Page::create(['title' => 'About us']);
    Product::create(['name' => 'Blue Widget']);

    expect(Slug::query()->count())->toBe(1); // Product has no "title" attribute
});

test('a generated slug can be replaced explicitly', function (): void {
    $page = Page::create(['title' => 'About us']);

    $page->setSlug('company');

    expect($page->slug())->toBe('company')
        ->and(Slug::query()->count())->toBe(1);
});

test('a colliding slug gets a numeric suffix', function (): void {
    $first = Page::create(['title' => 'About us']);
    $second = Page::create(['title' => 'About us']);
    $third = Page::create(['title' => 'About us']);

    expect($first->slug())->toBe('about-us')
        ->and($second->slug())->toBe('about-us-2')
        ->and($third->slug())->toBe('about-us-3');
});

test('a model may override the source attribute', function (): void {
    // Config still names "title", but this model derives from "name".
    NamedProduct::create(['name' => 'Blue Widget']);

    expect(NamedProduct::query()->first()?->slug())->toBe('blue-widget');
});

test('a model may opt out of automatic slugs', function (): void {
    $post = UnsluggedPost::create(['title' => 'Hello world']);

    expect($post->slug())->toBeNull()
        ->and(Slug::query()->count())->toBe(0);
});

test('opting out does not stop explicit slug writing', function (): void {
    $post = UnsluggedPost::create(['title' => 'Hello world']);

    expect($post->setSlugFrom('Hello world')->slug())->toBe('hello-world');
});
