<?php

namespace Pavloniym\NginxCache\Types;


use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;

class SimpleWithIpCache extends NginxCacheType
{
    public string $key = '$host|$request_uri|$request_method|$request_body|$http_x_forwarded_for|$remote_addr';
    public string $duration = '60s';
    public string $responses = 'any';
}
