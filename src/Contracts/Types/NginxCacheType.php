<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Contracts\Types;

use Pavloniym\NginxCache\Attributes\NginxCache;

abstract class NginxCacheType
{
    public string $key = '';
    public string $duration = '60s';
    public string $responses = 'any';

    /**
     * Get cache key
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get cache valid responses
     *
     * @return string
     */
    public function getResponses(): string
    {
        return $this->responses;
    }

    /**
     * Get cache valid duration
     *
     * @return string
     */
    public function getDuration(): string
    {
        return $this->duration;
    }

    /**
     * Get location config
     *
     * @param NginxCache $nginxCache
     * @return string|null
     */
    public function getConfiguration(NginxCache $nginxCache): ?string
    {
        $location = $nginxCache->getLocation();
        $cacheKey = $nginxCache->getKey();
        $cacheValidDuration = $nginxCache->getDuration();
        $cacheValidResponses = $nginxCache->getResponses();

        return <<<EOD
        location $location { proxy_cache_key "$cacheKey"; proxy_cache_valid $cacheValidResponses $cacheValidDuration; proxy_pass \$backend; }
        location $location/ { proxy_cache_key "$cacheKey"; proxy_cache_valid $cacheValidResponses $cacheValidDuration; proxy_pass \$backend; }
        EOD;
    }
}
