<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Unit;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Pavloniym\NginxCache\Attributes\NginxCache;
use Pavloniym\NginxCache\Tests\TestCase;
use Pavloniym\NginxCache\Types\SimpleCache;
use Pavloniym\NginxCache\Types\UserCache;
use Pavloniym\NginxCache\Types\WithoutCache;

class NginxCacheAttributeTest extends TestCase
{
    public function test_it_generates_regex_location_for_dynamic_paths(): void
    {
        $cache = new NginxCache(type: SimpleCache::class, location: 'api/orders/{id}');

        self::assertSame('/api/orders/.*', $cache->getRawLocation());
        self::assertSame('~ /api/orders/.*', $cache->getLocation());
        self::assertFalse($cache->isExactLocation());
    }

    public function test_it_generates_exact_location_for_static_paths(): void
    {
        $cache = new NginxCache(type: SimpleCache::class, location: 'api/orders');

        self::assertSame('/api/orders', $cache->getRawLocation());
        self::assertSame('= /api/orders', $cache->getLocation());
        self::assertTrue($cache->isExactLocation());
    }

    public function test_user_cache_key_uses_session_cookie_from_config(): void
    {
        config()->set('session.cookie', 'custom_session_cookie');

        $cache = new NginxCache(type: UserCache::class);

        self::assertSame(
            '$host|$request_uri|$request_method|$request_body|$cookie_custom_session_cookie|$http_authorization',
            $cache->getKey()
        );
    }

    public function test_it_uses_overrides_for_key_duration_and_responses(): void
    {
        $cache = new NginxCache(
            type: SimpleCache::class,
            key: '$request_uri|$request_method',
            duration: 10,
            responses: '200 301'
        );

        self::assertSame('$request_uri|$request_method', $cache->getKey());
        self::assertSame('10s', $cache->getDuration());
        self::assertSame('200 301', $cache->getResponses());
    }

    public function test_it_uses_type_defaults_for_key_duration_and_responses_when_not_overridden(): void
    {
        $cache = new NginxCache(type: SimpleCache::class);

        self::assertSame('$request_uri|$request_method|$request_body', $cache->getKey());
        self::assertSame('60s', $cache->getDuration());
        self::assertSame('any', $cache->getResponses());
    }

    public function test_it_uses_route_uri_when_location_is_not_provided(): void
    {
        Route::get('api/route-based-location/{slug}', static fn(): array => []);

        /** @var RoutingRoute $route */
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(static fn(RoutingRoute $item): bool => $item->uri() === 'api/route-based-location/{slug}');

        $cache = (new NginxCache(type: SimpleCache::class))->setRoute($route);

        self::assertSame('/api/route-based-location/.*', $cache->getRawLocation());
        self::assertSame('~ /api/route-based-location/.*', $cache->getLocation());
    }

    public function test_get_configuration_returns_null_for_without_cache_type(): void
    {
        $cache = new NginxCache(type: WithoutCache::class, location: 'api/no-cache');

        self::assertNull($cache->getConfiguration());
        self::assertSame('0s', $cache->getDuration());
        self::assertSame('any', $cache->getResponses());
    }
}
