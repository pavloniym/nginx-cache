<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers;

use Pavloniym\NginxCache\Attributes\NginxCache;

class BrokenAttributeController
{
    #[NginxCache(type: 'Pavloniym\\NginxCache\\Types\\TypeThatDoesNotExist')]
    public function broken(): array
    {
        return [];
    }
}
