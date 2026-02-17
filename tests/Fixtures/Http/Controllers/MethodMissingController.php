<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Tests\Fixtures\Http\Controllers;

class MethodMissingController
{
    public function existing(): array
    {
        return [];
    }
}
