<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use BasekitLaravel\BasekitLaravelSlugs\SlugGenerator;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Product;
use Illuminate\Support\Facades\DB;

test('whereSlugIn resolves a batch of slugs without an N+1', function (): void {
    Page::create()->setSlug('about');
    Page::create()->setSlug('contact');
    Page::create()->setSlug('careers');

    DB::enableQueryLog();

    $slugs = Page::whereSlugIn(['about', 'careers'])
        ->with('slugs')
        ->get()
        ->map(fn (Page $page): ?string => $page->slug())
        ->sort()
        ->values()
        ->all();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($slugs)->toBe(['about', 'careers'])
        ->and($queries)->toBe(2);
});

test('whereSlugIn honours an explicit locale', function (): void {
    $page = Page::create()->setSlug('about');
    $page->setSlug('acerca-de', 'es');

    $esSlugs = Page::whereSlugIn(['about', 'acerca-de'], 'es')
        ->with('slugs')
        ->get()
        ->map(fn (Page $found): ?string => $found->slug('es'))
        ->all();

    expect($esSlugs)->toBe(['acerca-de'])
        ->and(Page::whereSlugIn(['about', 'acerca-de'], 'de')->count())->toBe(0)
        ->and(Page::whereSlugIn(['about', 'acerca-de'])->count())->toBe(1);
});

test('whereSlugIn ignores slugs that belong to other model types', function (): void {
    Page::create()->setSlug('about');
    Product::create(['name' => 'Chair'])->setSlug('chair');

    $this->assertSame(1, Page::whereSlugIn(['about', 'chair'])->count());
    $this->assertSame(1, Product::whereSlugIn(['about', 'chair'])->count());
});

test('whereSlugIn matches nothing for an empty list', function (): void {
    Page::create()->setSlug('about');

    $this->assertSame(0, Page::whereSlugIn([])->count());
    $this->assertSame(0, Page::whereSlugIn(new ArrayIterator([]))->count());
});

test('the container resolves the configured generator', function (): void {
    config([
        'basekit-laravel-slugs.generator.separator' => '_',
        'basekit-laravel-slugs.generator.preserve_unicode' => true,
    ]);

    app()->forgetInstance(SlugGenerator::class);

    $generator = app(SlugGenerator::class);

    expect($generator)->toBeInstanceOf(SlugGenerator::class)
        ->and($generator->isValid('españa'))->toBeTrue()
        ->and($generator->isValid('acerca de'))->toBeFalse()
        ->and($generator->generate('Hello World'))->toBe('hello_world');
});

test('the container resolves one shared generator instance', function (): void {
    expect(app(SlugGenerator::class))->toBe(app(SlugGenerator::class));
});

test('the resolved generator matches the configuration', function (): void {
    config(['basekit-laravel-slugs.generator.separator' => '_']);

    app()->forgetInstance(SlugGenerator::class);

    expect(app(SlugGenerator::class)->generate('Hello World'))->toBe('hello_world');
});

test('the slug factory creates a row for a given model', function (): void {
    $page = Page::create();

    $slug = Slug::factory()->forSluggable($page)->create();

    expect($slug)->toBeInstanceOf(Slug::class)
        ->and($slug->sluggable_type)->toBe(Page::class)
        ->and((int) $slug->sluggable_id)->toBe((int) $page->getKey())
        ->and($page->fresh()->hasSlug())->toBeTrue();
});

test('the slug factory can target a specific locale', function (): void {
    $page = Page::create();

    $slug = Slug::factory()->forSluggable($page, 'de')->create();

    expect($slug->locale)->toBe('de')
        ->and($page->fresh()->slug())->toBeNull()
        ->and($page->fresh()->slug('de'))->toBe($slug->slug);
});
