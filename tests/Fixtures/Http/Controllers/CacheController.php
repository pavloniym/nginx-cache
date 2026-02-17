<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers;

use Pavloniym\NginxCache\Attributes\NginxCache;
use Pavloniym\NginxCache\Types\SimpleCache;
use Pavloniym\NginxCache\Types\WithoutCache;

class CacheController
{
    #[NginxCache(type: SimpleCache::class, duration: 300, responses: '200')]
    public function products(): array
    {
        return [];
    }

    #[NginxCache(type: SimpleCache::class, location: 'api/products')]
    public function productsDuplicate(): array
    {
        return [];
    }

    #[NginxCache(type: SimpleCache::class)]
    public function productById(): array
    {
        return [];
    }

    #[NginxCache(type: WithoutCache::class)]
    public function noCacheEndpoint(): array
    {
        return [];
    }

    public function plainEndpoint(): array
    {
        return [];
    }
}
