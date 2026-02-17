<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Unit;

use Pavloniym\NginxCache\Attributes\NginxCache;
use Pavloniym\NginxCache\Tests\TestCase;
use Pavloniym\NginxCache\Types\SimpleCache;
use Pavloniym\NginxCache\Types\SimpleWithCountryIsoCache;
use Pavloniym\NginxCache\Types\SimpleWithIpCache;
use Pavloniym\NginxCache\Types\UserCache;

class NginxCacheTypesTest extends TestCase
{
    public function test_simple_cache_defaults(): void
    {
        $type = new SimpleCache();

        self::assertSame('$request_uri|$request_method|$request_body', $type->getKey());
        self::assertSame('60s', $type->getDuration());
        self::assertSame('any', $type->getResponses());
    }

    public function test_simple_with_country_iso_defaults(): void
    {
        $type = new SimpleWithCountryIsoCache();

        self::assertSame('$host|$request_uri|$request_method|$request_body|$geoip2_data_country_code', $type->getKey());
        self::assertSame('60s', $type->getDuration());
        self::assertSame('any', $type->getResponses());
    }

    public function test_simple_with_ip_defaults(): void
    {
        $type = new SimpleWithIpCache();

        self::assertSame('$host|$request_uri|$request_method|$request_body|$http_x_forwarded_for|$remote_addr', $type->getKey());
        self::assertSame('60s', $type->getDuration());
        self::assertSame('any', $type->getResponses());
    }

    public function test_user_cache_defaults(): void
    {
        config()->set('session.cookie', 'session_cookie_name');
        $type = new UserCache();

        self::assertSame('$host|$request_uri|$request_method|$request_body|$cookie_session_cookie_name|$http_authorization', $type->getKey());
        self::assertSame('1s', $type->getDuration());
        self::assertSame('any', $type->getResponses());
    }

    public function test_default_type_configuration_renders_two_locations(): void
    {
        $cache = new NginxCache(type: SimpleCache::class, location: 'api/config-test');
        $configuration = (new SimpleCache())->getConfiguration($cache);

        self::assertNotNull($configuration);
        self::assertStringContainsString('location = /api/config-test {', $configuration);
        self::assertStringContainsString('location = /api/config-test/ {', $configuration);
        self::assertStringContainsString('proxy_cache_valid any 60s;', $configuration);
    }
}
