<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Post;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the slugs table is created by the package migration', function (): void {
    $this->assertTrue(Schema::hasTable('slugs'));
});

test('a model can persist a slug for its locale', function (): void {
    $page = Page::create(['title' => 'About']);

    $page->setSlug('about');

    $this->assertSame('about', $page->slug());

    $row = DB::table('slugs')->sole();
    $this->assertSame(Page::class, $row->sluggable_type);
    $this->assertSame($page->getKey(), (int) $row->sluggable_id);
    $this->assertSame('en', $row->locale);
    $this->assertSame('about', $row->slug);
});

test('setting a slug again updates it instead of inserting a second row', function (): void {
    $page = Page::create()->setSlug('about');

    $page->setSlug('about-us');

    $this->assertSame('about-us', $page->slug());
    $this->assertSame(1, DB::table('slugs')->count());
});

test('setSlug stores the given value verbatim', function (): void {
    $page = Page::create()->setSlug('My  Title');

    $this->assertSame('My  Title', $page->slug());
});

test('setSlugFrom generates a slug from an arbitrary value first', function (): void {
    $page = Page::create(['title' => 'Hello World!']);

    $page->setSlugFrom($page->title);

    $this->assertSame('hello-world', $page->slug());
});

test('passing null removes the slug for a locale', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    $page->setSlug(null, 'es');

    $this->assertNull($page->slug('es'));
    $this->assertSame('about', $page->slug());
    $this->assertSame(1, DB::table('slugs')->count());
});

test('slug returns null when the locale has no slug', function (): void {
    $page = Page::create()->setSlug('about');

    $this->assertNull($page->slug('es'));
    $this->assertNull($page->slug('hu'));
});

test('hasSlug reflects whether a locale has a slug', function (): void {
    $page = Page::create()->setSlug('about');

    $this->assertTrue($page->hasSlug());
    $this->assertFalse($page->hasSlug('es'));
});

test('slugMap returns every slug keyed by locale', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    $this->assertSame(['en' => 'about', 'es' => 'sobre-nosotros'], $page->slugMap());
});

test('the slugs relation returns ordered Slug models', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('over-ons', 'nl');

    $slugs = $page->slugs;

    $this->assertCount(2, $slugs);
    $this->assertContainsOnlyInstancesOf(Slug::class, $slugs);
    $this->assertSame(['about', 'over-ons'], $slugs->pluck('slug')->all());
    $this->assertSame(['en', 'nl'], $slugs->pluck('locale')->all());
});

test('a loaded slugs relation serves locale reads without extra queries', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    DB::enableQueryLog();

    $page = Page::with('slugs')->firstOrFail();
    $page->slug();
    $page->slug('es');
    $page->hasSlug('en');

    $this->assertCount(2, DB::getQueryLog());
    DB::disableQueryLog();
});

test('whereSlug finds a model by its default-locale slug', function (): void {
    $first = Page::create()->setSlug('about');
    Page::create()->setSlug('contact');

    $found = Page::whereSlug('about')->first();

    $this->assertTrue($found->is($first));
});

test('whereSlug honours an explicit locale', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    $this->assertTrue(Page::whereSlug('sobre-nosotros', 'es')->firstOrFail()->is($page));
    $this->assertNull(Page::whereSlug('sobre-nosotros')->first());
});

test('whereSlug only returns models of the queried class', function (): void {
    Page::create()->setSlug('about');
    Post::create()->setSlug('about');

    $this->assertSame(1, Page::whereSlug('about')->count());
    $this->assertSame(1, Post::whereSlug('about')->count());
});

test('deleting a model deletes its slug rows', function (): void {
    $page = Page::create();
    $page->setSlug('about');
    $page->setSlug('sobre-nosotros', 'es');

    $page->delete();

    $this->assertSame(0, DB::table('slugs')->count());
});

test('soft-deleted models keep their slugs until force deleted', function (): void {
    $product = Product::create(['name' => 'Chair']);
    $product->setSlug('chair');

    $product->delete();

    $this->assertSame(1, DB::table('slugs')->count());
    $this->assertSame('chair', Product::withTrashed()->findOrFail($product->getKey())->slug());

    Product::withTrashed()->findOrFail($product->getKey())->forceDelete();

    $this->assertSame(0, DB::table('slugs')->count());
});

test('a delete aborted by another listener leaves the model and its slug intact', function (): void {
    $page = Page::create()->setSlug('about');

    Page::deleting(fn (): bool => false);

    $this->assertFalse($page->delete());
    $this->assertSame(1, DB::table('slugs')->count());
    $this->assertSame('about', Page::findOrFail($page->getKey())->slug());
});
