<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSlugs\Tests;

use BasekitLaravel\BasekitLaravelSlugs\BasekitLaravelSlugsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Every table the suite builds. Dropped before each test so the same
     * TestCase works against a throwaway in-memory SQLite database and against
     * a real MySQL or PostgreSQL server, where state would otherwise survive
     * between tests.
     *
     * @var list<string>
     */
    private const TABLES = ['migrations', 'slugs', 'pages', 'posts', 'products', 'articles'];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            BasekitLaravelSlugsServiceProvider::class,
        ];
    }

    #[\Override]
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->databaseConfiguration());

        $app['config']->set('app.url', 'https://example.test');
    }

    /**
     * Defaults to in-memory SQLite. Set TEST_DB_CONNECTION (plus the matching
     * TEST_DB_* variables) to run the suite against another driver; the CI
     * workflow does this for MySQL and PostgreSQL.
     */
    private function databaseConfiguration(): array
    {
        $driver = (string) env('TEST_DB_CONNECTION', 'sqlite');

        return match ($driver) {
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('TEST_DB_HOST', '127.0.0.1'),
                'port' => env('TEST_DB_PORT', '3306'),
                'database' => env('TEST_DB_DATABASE', 'slugs'),
                'username' => env('TEST_DB_USERNAME', 'root'),
                'password' => env('TEST_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('TEST_DB_HOST', '127.0.0.1'),
                'port' => env('TEST_DB_PORT', '5432'),
                'database' => env('TEST_DB_DATABASE', 'slugs'),
                'username' => env('TEST_DB_USERNAME', 'postgres'),
                'password' => env('TEST_DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
            default => throw new InvalidArgumentException("Unsupported TEST_DB_CONNECTION [{$driver}]."),
        };
    }

    private function setUpDatabase(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        $migration = include __DIR__.'/../database/migrations/2026_01_01_000001_create_slugs_table.php';
        $migration->up();

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }
}
