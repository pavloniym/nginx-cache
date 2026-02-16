<?php

namespace Pavloniym\NginxCache;

use Illuminate\Support\ServiceProvider;
use Pavloniym\NginxCache\Commands\BuildNginxCacheRoutesCommand;
use Pavloniym\NginxCache\Commands\ListNginxCacheRoutesCommand;

class NginxCacheServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Объединяем дефолтный конфиг пакета с тем, который создаст пользователь
        // Это позволяет не прописывать все ключи в проекте, если они не изменены
        if (file_exists(__DIR__ . '/../config/nginx-cache.php')) {
            $this->mergeConfigFrom(__DIR__ . '/../config/nginx-cache.php', 'nginx-cache');
        }
    }

    public function boot()
    {
        // Позволяем пользователю опубликовать конфиг командой:
        // php artisan vendor:publish --tag=config
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/nginx-cache.php' => config_path('nginx-cache.php'),], 'config');
            $this->commands([
                ListNginxCacheRoutesCommand::class,
                BuildNginxCacheRoutesCommand::class,
            ]);
        }
    }
}