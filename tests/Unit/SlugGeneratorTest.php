<?php

declare(strict_types=1);

use BasekitLaravel\BasekitLaravelSlugs\SlugGenerator;

function generator(bool $preserveUnicode = false): SlugGenerator
{
    return new SlugGenerator(preserveUnicode: $preserveUnicode);
}

test('a simple title becomes a lowercase hyphenated slug', function (): void {
    expect(generator()->generate('Hello World'))->toBe('hello-world');
});

test('multiple spaces and tabs collapse into a single separator', function (): void {
    expect(generator()->generate('hello     world'))->toBe('hello-world');
    expect(generator()->generate("hello\tworld"))->toBe('hello-world');
});

test('punctuation is replaced by the separator', function (): void {
    expect(generator()->generate('Hello, world!'))->toBe('hello-world');
    expect(generator()->generate('Post #42'))->toBe('post-42');
});

test('input is lowercased deterministically', function (): void {
    expect(generator()->generate('HELLO-WORLD'))->toBe('hello-world');
    expect(generator()->generate('MY FIRST POST'))->toBe('my-first-post');
});

test('existing separators and underscores are normalized', function (): void {
    expect(generator()->generate('hello_world'))->toBe('hello-world');
    expect(generator()->generate('hello--world'))->toBe('hello-world');
});

test('leading and trailing separators are trimmed', function (): void {
    expect(generator()->generate('---hello---'))->toBe('hello');
});

test('accented Latin characters are transliterated to ASCII by default', function (): void {
    expect(generator()->generate('España'))->toBe('espana');
    expect(generator()->generate('Déjà Vu'))->toBe('deja-vu');
    expect(generator()->generate('À PROPOS'))->toBe('a-propos');
});

test('accented characters are preserved when unicode mode is enabled', function (): void {
    expect(generator(preserveUnicode: true)->generate('España'))->toBe('españa');
    expect(generator(preserveUnicode: true)->generate('Déjà Vu'))->toBe('déjà-vu');
});

test('a custom separator is honoured', function (): void {
    expect((new SlugGenerator(separator: '_'))->generate('Hello World'))->toBe('hello_world');
});

test('empty and meaningless input produce a deterministic fallback slug', function (): void {
    expect(generator()->generate(''))->toBe('slug');
    expect(generator()->generate('!!!'))->toBe('slug');
    expect(generator()->generate('   '))->toBe('slug');
});

test('generation is deterministic for repeated calls', function (): void {
    $value = '  Hello,   Wörld!  ';

    expect(generator()->generate($value))->toBe(generator()->generate($value));
});

test('a well-formed slug is considered valid', function (): void {
    expect(generator()->isValid('about'))->toBeTrue();
    expect(generator()->isValid('my-first-post'))->toBeTrue();
    expect(generator()->isValid('post-42'))->toBeTrue();
});

test('a malformed slug is considered invalid', function (): void {
    expect(generator()->isValid(''))->toBeFalse();
    expect(generator()->isValid('Hello World'))->toBeFalse();
    expect(generator()->isValid('hello__world'))->toBeFalse();
    expect(generator()->isValid('-hello'))->toBeFalse();
    expect(generator()->isValid('hello-'))->toBeFalse();
});

test('unicode slugs are only valid for the unicode-aware generator', function (): void {
    expect(generator()->isValid('españa'))->toBeFalse();
    expect(generator(preserveUnicode: true)->isValid('españa'))->toBeTrue();
});
