<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the same slug cannot be used twice for the same model and locale', function (): void {
    Page::create()->setSlug('about');

    expect(fn () => Page::create()->setSlug('about'))
        ->toThrow(QueryException::class);

    $this->assertSame(1, DB::table('slugs')->count());
});

test('the same slug may be used for the same model across different locales', function (): void {
    Page::create()->setSlug('about');

    expect(fn () => Page::create()->setSlug('about', 'es'))->not->toThrow(QueryException::class);

    $this->assertSame(2, DB::table('slugs')->count());
});

test('the same slug may be reused across different model types', function (): void {
    Page::create()->setSlug('about');
    Post::create()->setSlug('about');

    $this->assertSame(2, DB::table('slugs')->count());
    $this->assertSame(1, Page::whereSlug('about')->count());
    $this->assertSame(1, Post::whereSlug('about')->count());
});

test('setting a slug for the same locale never duplicates a row', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('about-us');

    $this->assertSame(1, DB::table('slugs')->count());
    $this->assertSame(['en' => 'about-us'], $page->fresh()->slugMap());
});

test('one slug per locale is enforced at the database level', function (): void {
    $index = collect(Schema::getIndexes('slugs'))
        ->first(fn (array $index): bool => $index['columns'] === ['sluggable_type', 'sluggable_id', 'locale']);

    $this->assertNotNull($index);
    $this->assertTrue($index['unique']);
});

test('model-local slug uniqueness is enforced at the database level', function (): void {
    $index = collect(Schema::getIndexes('slugs'))
        ->first(fn (array $index): bool => $index['columns'] === ['sluggable_type', 'locale', 'slug']);

    $this->assertNotNull($index);
    $this->assertTrue($index['unique']);
});
