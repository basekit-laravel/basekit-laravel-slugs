<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Database\Factories;

use BasekitLaravel\BasekitLaravelSlugs\Models\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Slug>
 */
class SlugFactory extends Factory
{
    /**
     * @var class-string<Slug>
     */
    protected $model = Slug::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'locale' => 'en',
            'slug' => Str::slug($this->faker->unique()->words(3, true)),
        ];
    }

    /**
     * Attach the generated row to a model, filling in the polymorphic pair.
     */
    public function forSluggable(Model $model, ?string $locale = null): static
    {
        return $this->state(fn (): array => [
            'sluggable_type' => $model->getMorphClass(),
            'sluggable_id' => $model->getKey(),
            'locale' => $locale ?? config('basekit-laravel-slugs.default_locale')
                ?? config('app.locale', 'en'),
        ]);
    }
}
