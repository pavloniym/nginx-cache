<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Pavloniym\NginxCache\Attributes\NginxCache;
use ReflectionAttribute;
use ReflectionMethod;
use Throwable;

class ListNginxCacheRoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nginx-cache:list';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse HTTP Api Controllers and show nginx cache configs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->table(
            ['route', 'cache', 'duration', 'responses', 'key', 'location'],
            $this->getApiRoutes()->map(static function (array $item): array {


                /** @var \Illuminate\Routing\Route $route */
                $route = $item[0] ?? null;

                /** @var NginxCache $nginxCache */
                $nginxCache = $item[1] ?? null;

                return [
                    $route->uri(),
                    $nginxCache?->type?->value,
                    $nginxCache?->getDuration(),
                    $nginxCache?->getResponses(),
                    $nginxCache?->getKey(),
                    $nginxCache?->getLocation(),
                ];
            })
        );

    }

    protected function getApiRoutes(): iterable
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(static fn(\Illuminate\Routing\Route $route) => Str::startsWith($route->uri(), ['api/', '/api/']))
            ->map(static function (\Illuminate\Routing\Route $route): array {
                try {

                    $attributeOfMethod = collect((new ReflectionMethod($route->getControllerClass(), $route->getActionMethod()))->getAttributes(NginxCache::class))
                        ->map(static fn(?ReflectionAttribute $attribute): ?object => $attribute?->newInstance())
                        ->filter()
                        ->first();

                    if ($attributeOfMethod instanceof NginxCache) {

                        return [
                            $route,
                            $attributeOfMethod->setRoute(route: $route)
                        ];
                    }

                } catch (Throwable $exception) {
                    dump($exception);
                }

                return [$route, null];
            })
            ->values();
    }

    /**
     * @return iterable<NginxCache>
     */
    protected function getApiRoutesWithCache(): iterable
    {
        return $this
            ->getApiRoutes()
            ->map(static fn(array $item) => $item[1] ?? null)
            ->filter()
            ->values();
    }
}
