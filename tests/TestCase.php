<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Pavloniym\NginxCache\NginxCacheServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NginxCacheServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('session.cookie', 'laravel_session');
    }
}
