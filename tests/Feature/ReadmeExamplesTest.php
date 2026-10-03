<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use BasekitLaravel\BasekitLaravelSlugs\SlugGenerator;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use Illuminate\Database\UniqueConstraintViolationException;

test('readme quick start example', function (): void {
    $page = Page::create(['title' => 'About us']);

    $page->setSlug('about');
    $page->setSlug('acerca-de-nosotros', 'es');

    expect($page->slug())->toBe('about')
        ->and($page->slug('es'))->toBe('acerca-de-nosotros')
        ->and($page->slug('de'))->toBeNull()
        ->and($page->hasSlug('es'))->toBeTrue()
        ->and($page->slugMap())->toBe(['en' => 'about', 'es' => 'acerca-de-nosotros']);
});

test('readme setSlugFrom and generator examples', function (): void {
    $page = Page::create(['title' => 'About us']);
    $second = Page::create(['title' => 'Contact']);

    expect($page->setSlugFrom($page->title)->slug())->toBe('about-us')
        ->and($second->setSlugFrom('About us')->slug())->toBe('about-us-2');

    $generator = app(SlugGenerator::class);

    expect($generator->generate('España'))->toBe('espana')
        ->and($generator->isValid('about-us'))->toBeTrue();
});

test('readme lookup examples', function (): void {
    Page::create(['title' => 'About us'])->setSlug('about');
    Page::create(['title' => 'Pricing'])->setSlug('pricing');

    expect(Page::whereSlug('about')->first())->not->toBeNull()
        ->and(Page::whereSlugIn(['about', 'pricing'])->count())->toBe(2)
        ->and(Page::whereSlugIn([])->count())->toBe(0);
});

test('readme factory example', function (): void {
    $page = Page::create(['title' => 'About']);

    Slug::factory()->forSluggable($page)->create(['slug' => 'about']);
    Slug::factory()->forSluggable($page, 'es')->create(['slug' => 'acerca']);

    expect($page->slug())->toBe('about')
        ->and($page->slug('es'))->toBe('acerca');
});

test('readme automatic slug on create example', function (): void {
    config()->set('basekit-laravel-slugs.auto_slug', [
        'enabled' => true,
        'attribute' => 'title',
    ]);

    $page = Page::create(['title' => 'About us']);

    expect($page->slug())->toBe('about-us');
});

test('readme duplicate suffix example', function (): void {
    config()->set('basekit-laravel-slugs.auto_slug.enabled', true);
    config()->set('basekit-laravel-slugs.disambiguate', true);

    expect(Page::create(['title' => 'About us'])->slug())->toBe('about-us')
        ->and(Page::create(['title' => 'About us'])->slug())->toBe('about-us-2')
        ->and(Page::create(['title' => 'About us'])->slug())->toBe('about-us-3');
});

test('readme disambiguation turned off example', function (): void {
    config()->set('basekit-laravel-slugs.auto_slug.enabled', true);
    config()->set('basekit-laravel-slugs.disambiguate', false);

    Page::create(['title' => 'About us']);

    expect(fn () => Page::create(['title' => 'About us']))
        ->toThrow(UniqueConstraintViolationException::class);
});
