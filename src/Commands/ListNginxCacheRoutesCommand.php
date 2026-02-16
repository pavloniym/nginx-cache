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
    protected $signature = 'nginx-cache:list {--all : Show all routes, not only with cache attribute}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse HTTP Api Controllers and show nginx cache routes list';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $routes = $this->getApiRoutes();

        // Если не указан флаг --all, показываем только роуты с кешем
        if (!$this->option('all')) {
            $routes = $routes->filter(static fn(array $item): bool => $item[1] !== null);
        }

        $this->table(
            ['route', 'cache', 'duration', 'responses', 'key', 'location'],
            $routes->map(static function (array $item): array {


                /** @var \Illuminate\Routing\Route $route */
                $route = $item[0] ?? null;

                /** @var NginxCache $nginxCache */
                $nginxCache = $item[1] ?? null;

                return [
                    $route->uri(),
                    $nginxCache?->type ? class_basename($nginxCache->type) : null,
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
            ->filter(static function (\Illuminate\Routing\Route $route): bool {
                $action = $route->getAction();
                return isset($action['controller']) && is_string($action['controller']);
            })
            ->map(static function (\Illuminate\Routing\Route $route): array {
                try {
                    [$controller, $method] = Str::parseCallback($route->getAction('controller'));

                    if (!class_exists($controller) || !method_exists($controller, $method)) {
                        return [$route, null];
                    }

                    $attribute = collect((new ReflectionMethod($controller, $method))->getAttributes(NginxCache::class))
                        ->first()?->newInstance();

                    return $attribute instanceof NginxCache
                        ? [$route, $attribute->setRoute($route)]
                        : [$route, null];

                } catch (Throwable) {
                    return [$route, null];
                }
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
