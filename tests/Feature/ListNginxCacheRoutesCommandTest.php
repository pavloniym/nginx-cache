<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers\BrokenAttributeController;
use Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers\CacheController;
use Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers\MethodMissingController;
use Pavloniym\NginxCache\Tests\TestCase;

class ListNginxCacheRoutesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('api/products', [CacheController::class, 'products']);
        Route::get('api/products-duplicate', [CacheController::class, 'productsDuplicate']);
        Route::get('api/products/{id}', [CacheController::class, 'productById']);
        Route::get('api/no-cache', [CacheController::class, 'noCacheEndpoint']);
        Route::get('api/plain', [CacheController::class, 'plainEndpoint']);
        Route::get('api/no-controller-string', static fn(): array => []);
        $missingClassRoute = Route::get('api/missing-class', [MethodMissingController::class, 'existing']);
        $missingClassRoute->setAction(array_merge(
            $missingClassRoute->getAction(),
            ['controller' => 'App\\Http\\Controllers\\ControllerThatDoesNotExist@missing']
        ));
        Route::get('api/missing-method', MethodMissingController::class . '@missing');
        $parseThrowsRoute = Route::get('api/parse-callback-throws', [MethodMissingController::class, 'existing']);
        $parseThrowsRoute->setAction(array_merge(
            $parseThrowsRoute->getAction(),
            ['controller' => MethodMissingController::class]
        ));
        Route::get('api/broken-attribute', [BrokenAttributeController::class, 'broken']);
    }

    public function test_list_shows_only_routes_with_nginx_cache_attribute_by_default(): void
    {
        Artisan::call('nginx-cache:list');
        $output = Artisan::output();

        self::assertStringContainsString('api/products', $output);
        self::assertStringContainsString('api/products/{id}', $output);
        self::assertStringContainsString('api/no-cache', $output);
        self::assertStringNotContainsString('api/plain', $output);
    }

    public function test_list_with_all_option_includes_plain_routes(): void
    {
        Artisan::call('nginx-cache:list', ['--all' => true]);
        $output = Artisan::output();

        self::assertStringContainsString('api/plain', $output);
        self::assertStringContainsString('api/missing-class', $output);
        self::assertStringContainsString('api/missing-method', $output);
        self::assertStringContainsString('api/parse-callback-throws', $output);
        self::assertStringContainsString('api/broken-attribute', $output);
        self::assertStringNotContainsString('api/no-controller-string', $output);
    }

    public function test_list_renders_empty_cache_columns_for_non_resolvable_controller_actions(): void
    {
        Artisan::call('nginx-cache:list', ['--all' => true]);
        $output = Artisan::output();

        self::assertMatchesRegularExpression('/api\/missing-class\s+\|\s+\|\s+\|\s+\|\s+\|/', $output);
        self::assertMatchesRegularExpression('/api\/missing-method\s+\|\s+\|\s+\|\s+\|\s+\|/', $output);
        self::assertMatchesRegularExpression('/api\/parse-callback-throws\s+\|\s+\|\s+\|\s+\|\s+\|/', $output);
        self::assertMatchesRegularExpression('/api\/broken-attribute\s+\|\s+\|\s+\|\s+\|\s+\|/', $output);
    }
}
