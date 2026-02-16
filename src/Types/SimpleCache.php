<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Types;

use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;

class SimpleCache extends NginxCacheType
{
    public string $key = '$request_uri|$request_method|$request_body';
    public string $duration = '60s';
    public string $responses = 'any';
}
