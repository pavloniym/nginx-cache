<?php

namespace Pavloniym\NginxCache\Types;


use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;

class SimpleWithCountryIsoCache extends NginxCacheType
{
    public string $key = '$host|$request_uri|$request_method|$request_body|$geoip2_data_country_code';
    public string $duration = '60s';
    public string $responses = 'any';
}
