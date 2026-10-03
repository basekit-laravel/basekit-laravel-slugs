<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

afterEach(function (): void {
    File::deleteDirectory(database_path('migrations'));
});

test('the packaged migration is not auto-loaded', function (): void {
    $packageMigrations = realpath(__DIR__.'/../../database/migrations');

    $registered = array_map(
        static fn (string $path): string => realpath($path) ?: $path,
        app('migrator')->paths(),
    );

    expect($registered)->not->toContain($packageMigrations);
});

test('the documented install flow publishes the migration and migrates cleanly', function (): void {
    File::ensureDirectoryExists(database_path('migrations'));

    // Step 1 from the README.
    Artisan::call('vendor:publish', ['--tag' => 'basekit-laravel-slugs-migrations', '--force' => true]);

    $published = array_values(array_filter(
        File::files(database_path('migrations')),
        static fn (SplFileInfo $file): bool => str_contains($file->getFilename(), 'create_slugs_table'),
    ));

    expect($published)->toHaveCount(1);

    // Start from a clean slate: the TestCase built the schema directly.
    Schema::drop('slugs');

    // Step 2 from the README. This is the step that used to fail with
    // "table slugs already exists" when the migration was also auto-loaded.
    Artisan::call('migrate', ['--force' => true]);

    expect(Schema::hasTable('slugs'))->toBeTrue();
    expect(DB::table('migrations')->where('migration', 'like', '%create_slugs_table')->count())->toBe(1);
});

test('the published config can be customised after publishing', function (): void {
    File::ensureDirectoryExists(config_path());

    Artisan::call('vendor:publish', ['--tag' => 'basekit-laravel-slugs-config', '--force' => true]);

    $published = config_path('basekit-laravel-slugs.php');

    expect(File::exists($published))->toBeTrue();

    File::delete($published);
});
