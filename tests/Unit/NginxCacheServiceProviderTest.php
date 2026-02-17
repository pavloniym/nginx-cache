<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Pavloniym\NginxCache\Tests\TestCase;

class NginxCacheServiceProviderTest extends TestCase
{
    public function test_it_registers_package_config_defaults(): void
    {
        self::assertSame('/etc/nginx/conf.d/_cache', config('nginx-cache.path'));
        self::assertSame('locations.conf', config('nginx-cache.filename'));
    }

    public function test_it_registers_artisan_commands_in_console(): void
    {
        $commands = Artisan::all();

        self::assertArrayHasKey('nginx-cache:list', $commands);
        self::assertArrayHasKey('nginx-cache:build', $commands);
    }
}
