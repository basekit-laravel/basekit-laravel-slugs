<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs;

use Illuminate\Support\Str;

/**
 * Generates and validates URL-safe slugs from arbitrary strings.
 *
 * Generation is deterministic. Input is lowercased, optionally transliterated
 * to ASCII, then every run of characters that is neither a letter nor a number
 * is collapsed into the configured separator.
 *
 * By default accented Latin characters are transliterated to their ASCII
 * equivalents ("España" -> "espana"). Set preserveUnicode to true to keep
 * accented letters ("España" -> "españa"). Transliteration relies on
 * Laravel's Str::ascii(), whose coverage depends on the PHP intl extension;
 * without intl a built-in map still handles the common Latin accents.
 */
final readonly class SlugGenerator
{
    public function __construct(private string $separator = '-', private bool $preserveUnicode = false) {}

    /**
     * Turn an arbitrary string into a slug.
     *
     * The result is lowercase, contains only letters, numbers and separators,
     * and never starts or ends with a separator. Input that yields no slugable
     * characters at all produces the deterministic fallback "slug".
     */
    public function generate(string $value): string
    {
        $value = mb_strtolower($value);

        if (! $this->preserveUnicode) {
            $value = Str::ascii($value);
        }

        $value = (string) preg_replace('/[^\p{L}\p{N}]+/u', $this->separator, $value);

        $separator = preg_quote($this->separator, '/');
        $value = (string) preg_replace('/'.$separator.'{2,}/u', $this->separator, $value);

        $value = trim($value, $this->separator);

        return $value === '' ? 'slug' : $value;
    }

    /**
     * Whether a value is already a canonical slug under this generator's
     * rules: regenerating it yields the value itself. Because generation is
     * mode-aware, an accented slug is only valid for a generator configured
     * to preserve unicode.
     */
    public function isValid(string $value): bool
    {
        return $value !== '' && $value === $this->generate($value);
    }

    /**
     * Append a numeric suffix to an already generated slug, using the
     * configured separator so the result stays a canonical slug. Used to make
     * a colliding slug unique without changing its meaning.
     */
    public function withSuffix(string $slug, int $suffix): string
    {
        return $slug.$this->separator.$suffix;
    }
}
