<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Commands;

use Pavloniym\NginxCache\Attributes\NginxCache;

class BuildNginxCacheRoutesCommand extends ListNginxCacheRoutesCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nginx-cache:build';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse HTTP Controllers and make nginx cache configs';


    // nginx cache
    // api paths (laravel controllers) -> send to nginx
    // idea: parse all controller (only needed) -> create nginx config (additional) -> in config: /path/to/resource { cache_name: cache1 }
    // deploy version -> run command -> create config -> nginx reload -> use this config

    // laravel controller -> methods

    // 1 generate base nginx cache configs
    // 2 generate locations with these configs usage
    // location /....../ { proxy_cache: COMMON_CACHE}

    /**
     * @return void
     */
    public function handle(): void
    {
        $path = config('nginx-cache.path');
        $filename = config('nginx-cache.filename');

        file_put_contents(sprintf('%s/%s', $path, $filename), PHP_EOL . $this->getLocations(routes: $this->getApiRoutesWithCache()) . PHP_EOL, FILE_APPEND);
    }

    /**
     * @param iterable<NginxCache> $routes
     * @return string
     */
    private function getLocations(iterable $routes): string
    {
        return collect($routes)
            ->filter()
            ->unique(static fn(NginxCache $nginxCache): string => $nginxCache->type->value . $nginxCache->getLocation())
            ->map(static fn(NginxCache $nginxCache): ?string => $nginxCache->getConfiguration())
            ->filter()
            ->values()
            ->implode(PHP_EOL);
    }
}
