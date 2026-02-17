<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers\CacheController;
use Pavloniym\NginxCache\Tests\TestCase;

class BuildNginxCacheRoutesCommandTest extends TestCase
{
    private string $tmpConfigPath;
    private string $filename = 'locations.conf';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpConfigPath = sys_get_temp_dir() . '/nginx-cache-tests-' . uniqid('', true);

        config()->set('nginx-cache.path', $this->tmpConfigPath);
        config()->set('nginx-cache.filename', $this->filename);

        Route::get('api/products', [CacheController::class, 'products']);
        Route::get('api/products-duplicate', [CacheController::class, 'productsDuplicate']);
        Route::get('api/products/{id}', [CacheController::class, 'productById']);
        Route::get('api/no-cache', [CacheController::class, 'noCacheEndpoint']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmpConfigPath);

        parent::tearDown();
    }

    public function test_build_creates_file_with_generated_locations(): void
    {
        Artisan::call('nginx-cache:build');

        $path = $this->tmpConfigPath . '/' . $this->filename;

        self::assertFileExists($path);

        $content = File::get($path);

        self::assertStringContainsString('location = /api/products {', $content);
        self::assertSame(1, substr_count($content, 'location = /api/products {'));
        self::assertStringContainsString('proxy_cache_valid 200 300s;', $content);
        self::assertStringContainsString('location ~ /api/products/.* {', $content);
        self::assertStringNotContainsString('location = /api/no-cache {', $content);
    }

    public function test_build_appends_on_second_run(): void
    {
        Artisan::call('nginx-cache:build');
        Artisan::call('nginx-cache:build');

        $path = $this->tmpConfigPath . '/' . $this->filename;
        $content = File::get($path);

        self::assertSame(2, substr_count($content, 'location = /api/products {'));
        self::assertSame(2, substr_count($content, 'location ~ /api/products/.* {'));
    }

    public function test_build_works_when_target_directory_already_exists(): void
    {
        File::makeDirectory($this->tmpConfigPath, 0755, true);

        Artisan::call('nginx-cache:build');

        self::assertFileExists($this->tmpConfigPath . '/' . $this->filename);
    }
}
