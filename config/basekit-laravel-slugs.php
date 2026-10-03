<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default locale
    |--------------------------------------------------------------------------
    |
    | The locale used for slugs when none is given explicitly. When null the
    | application's default locale (config('app.locale')) is used, falling
    | back to "en".
    |
    */

    'default_locale' => null,

    /*
    |--------------------------------------------------------------------------
    | Duplicate slugs
    |--------------------------------------------------------------------------
    |
    | A slug may only be used once per model type and locale. When a slug the
    | package generates is already taken by another model of the same type, a
    | numeric suffix is added instead of failing: the second "About us" becomes
    | "about-us-2". This applies to every generated slug, whether it comes from
    | setSlugFrom() or from automatic generation on create. Slugs written with
    | setSlug() are always stored verbatim.
    |
    | Set to false to let the unique index reject duplicates instead.
    |
    */

    'disambiguate' => true,

    /*
    |--------------------------------------------------------------------------
    | Slug generator
    |--------------------------------------------------------------------------
    |
    | Controls how SlugGenerator turns arbitrary strings into slugs.
    |
    */

    'generator' => [

        // The separator inserted between the words of a slug.
        'separator' => '-',

        // When false, accented Latin letters are transliterated to ASCII
        // ("España" -> "espana"). When true they are preserved ("españa").
        'preserve_unicode' => false,

    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic slugs on create
    |--------------------------------------------------------------------------
    |
    | When enabled, creating a model that uses the HasSlugs trait also writes a
    | slug for the default locale, generated from the attribute below. Slugs
    | are only generated once, on create: later updates to the attribute never
    | change an existing slug, so published URLs stay stable.
    |
    | A model that already has a slug for the locale is left untouched.
    |
    */

    'auto_slug' => [

        // Set to true to generate a slug automatically whenever a model using
        // HasSlugs is created.
        'enabled' => false,

        // The model attribute the slug is generated from. Change this if your
        // models call it something else, such as "name" or "heading". A model
        // may override this by declaring slugSourceAttribute().
        'attribute' => 'title',

    ],

];
