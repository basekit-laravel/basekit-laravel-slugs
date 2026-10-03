<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\UnsluggedPost;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function (): void {
    config()->set('basekit-laravel-slugs.auto_slug.enabled', true);
    config()->set('basekit-laravel-slugs.auto_slug.attribute', 'title');
    config()->set('basekit-laravel-slugs.disambiguate', true);
});

test('automatic generation suffixes a colliding slug', function (): void {
    $first = Page::create(['title' => 'About us']);
    $second = Page::create(['title' => 'About us']);
    $third = Page::create(['title' => 'About us']);

    expect($first->slug())->toBe('about-us')
        ->and($second->slug())->toBe('about-us-2')
        ->and($third->slug())->toBe('about-us-3');
});

test('setSlugFrom suffixes a colliding slug the same way', function (): void {
    $first = UnsluggedPost::create()->setSlugFrom('About us');
    $second = UnsluggedPost::create()->setSlugFrom('About us');
    $third = UnsluggedPost::create()->setSlugFrom('About us');

    expect($first->slug())->toBe('about-us')
        ->and($second->slug())->toBe('about-us-2')
        ->and($third->slug())->toBe('about-us-3');
});

test('disambiguation uses the configured separator for both entry points', function (): void {
    config()->set('basekit-laravel-slugs.generator.separator', '_');

    Page::create(['title' => 'About us']);
    $automatic = Page::create(['title' => 'About us']);

    $first = UnsluggedPost::create()->setSlugFrom('About us');
    $second = UnsluggedPost::create()->setSlugFrom('About us');

    expect($automatic->slug())->toBe('about_us_2')
        ->and($first->slug())->toBe('about_us')
        ->and($second->slug())->toBe('about_us_2');
});

test('disambiguation skips numbers already taken', function (): void {
    Page::create(['title' => 'About us']);
    // Occupy the slot the next collision would otherwise take.
    Page::create(['title' => 'Other'])->setSlug('about-us-2');

    expect(Page::create(['title' => 'About us'])->slug())->toBe('about-us-3');
});

test('a model does not collide with the slug it already owns', function (): void {
    $page = Page::create(['title' => 'About us']);

    $page->setSlugFrom('About us');

    expect($page->fresh()->slug())->toBe('about-us')
        ->and(Slug::query()->where('slug', 'about-us')->count())->toBe(1);
});

test('disambiguation is scoped per locale', function (): void {
    Page::create(['title' => 'About us']);
    $page = Page::create(['title' => 'Something else']);

    $page->setSlugFrom('About us', 'es');

    expect($page->slug('es'))->toBe('about-us')
        ->and($page->slug())->toBe('something-else');
});

test('slugs stay unique per model type, so types may reuse a slug', function (): void {
    $page = Page::create(['title' => 'About us']);
    $post = UnsluggedPost::create()->setSlugFrom('About us');

    expect($page->slug())->toBe('about-us')
        ->and($post->slug())->toBe('about-us')
        ->and(Slug::query()->count())->toBe(2);
});

test('automatic generation rejects duplicates when disambiguation is off', function (): void {
    config()->set('basekit-laravel-slugs.disambiguate', false);

    Page::create(['title' => 'About us']);

    expect(fn () => Page::create(['title' => 'About us']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('setSlugFrom rejects duplicates when disambiguation is off', function (): void {
    config()->set('basekit-laravel-slugs.disambiguate', false);

    UnsluggedPost::create()->setSlugFrom('About us');

    expect(fn () => UnsluggedPost::create()->setSlugFrom('About us'))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('setSlug is never disambiguated', function (): void {
    Page::create()->setSlug('about-us');

    expect(fn () => Page::create()->setSlug('about-us'))
        ->toThrow(UniqueConstraintViolationException::class);
});
