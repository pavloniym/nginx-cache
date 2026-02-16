<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Types;


use Pavloniym\NginxCache\Attributes\NginxCache;
use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;

class WithoutCache extends NginxCacheType
{
    public string $key = '';
    public string $duration = '0s';
    public string $responses = 'any';

    /**
     * @param NginxCache $nginxCache
     * @return string|null
     */
    public function getConfiguration(NginxCache $nginxCache): ?string
    {
        return null;
    }
}
