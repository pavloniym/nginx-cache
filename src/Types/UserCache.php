<?php

namespace Pavloniym\NginxCache\Types;


use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;

class UserCache extends NginxCacheType
{
    public string $duration = '1s';
    public string $responses = 'any';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return sprintf('$host|$request_uri|$request_method|$request_body|$cookie_%s|$http_authorization', config('session.cookie'));
    }
}
