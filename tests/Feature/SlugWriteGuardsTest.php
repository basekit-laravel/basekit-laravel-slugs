<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Article;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\Page;
use BasekitLaravel\BasekitLaravelSlugs\Tests\TestSupport\Models\UidArticle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('writing a slug for an unsaved model explains that it must be saved first', function (): void {
    $page = new Page(['title' => 'About']);

    expect(fn () => $page->setSlug('about'))
        ->toThrow(LogicException::class, 'Cannot write a slug for an unsaved');
});

test('generating a slug for an unsaved model explains that it must be saved first', function (): void {
    $page = new Page(['title' => 'About']);

    expect(fn () => $page->setSlugFrom('About'))
        ->toThrow(LogicException::class, 'Cannot write a slug for an unsaved');
});

test('writing a slug for a string-keyed model explains how to support its key type', function (): void {
    $article = Article::create(['title' => 'Hello']);

    expect($article->getKey())->toBeString();

    expect(fn () => $article->setSlug('hello'))
        ->toThrow(LogicException::class, 'is not an integer');
});

test('the string-keyed model guard names the migration column to change', function (): void {
    $article = Article::create(['title' => 'Hello']);

    try {
        $article->setSlug('hello');
        $this->fail('Expected a LogicException.');
    } catch (LogicException $exception) {
        $this->assertStringContainsString('sluggable_id', $exception->getMessage());
        $this->assertStringContainsString('slugStorageSupportsStringKeys', $exception->getMessage());
    }

    $this->assertSame(0, DB::table('slugs')->count());
});

test('integer-keyed models are unaffected by the key type guard', function (): void {
    $page = Page::create()->setSlug('about');

    expect($page->slug())->toBe('about');
});

test('a ULID-keyed model owns slugs once the consumer widens sluggable_id and opts in', function (): void {
    // Mirrors what a consumer does to the published migration: widen the
    // polymorphic key pair to strings.
    Schema::table('slugs', function (Blueprint $table): void {
        $table->string('sluggable_type')->change();
        $table->string('sluggable_id')->change();
    });

    $article = UidArticle::create(['title' => 'Hello'])->setSlug('hello');

    expect($article->slug())->toBe('hello')
        ->and(DB::table('slugs')->where('sluggable_id', $article->getKey())->count())->toBe(1);
});
