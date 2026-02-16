<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Enums;

use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;
use Pavloniym\NginxCache\Types\SimpleCache;
use Pavloniym\NginxCache\Types\WithoutCache;

enum NginxCacheTypeEnum: string
{
    case SIMPLE = 'SIMPLE';
    case WITHOUT = 'WITHOUT';

    /**
     * @return NginxCacheType
     */
    public function getNginxCacheTypeInstance(): NginxCacheType
    {
        return match ($this) {
            self::SIMPLE => new SimpleCache,
            self::WITHOUT => new WithoutCache,
        };
    }
}
