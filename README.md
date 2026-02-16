# Laravel Nginx Cache

Laravel-пакет для автоматической генерации конфигурационных файлов кеширования Nginx на основе атрибутов в контроллерах.

## Оглавление

1. [Зачем это нужно]()
2. [Установка]()
3. [Настройка Nginx]()
4. [Использование]()
5. [Типы кеша]()
6. [Команды]()
7. [Кастомные типы]()
8. [Интеграция в CI/CD]()

---

## Зачем это нужно

Вместо ручного прописывания `location` блоков для кеширования API-ендпоинтов в конфигах Nginx, пакет сканирует контроллеры и генерирует конфиг автоматически. Вы используете атрибут `#[NginxCache]` на методе контроллера, а пакет берет на себя формирование корректных правил для Nginx.

## Установка

```bash
composer require pavloniym/nginx-cache

```

Опубликуйте конфигурационный файл:

```bash
php artisan vendor:publish --tag=config

```

В файле `config/nginx-cache.php` укажите путь, куда PHP должен записывать файл для Nginx:

```php
return [
    'path' => '/etc/nginx/conf.d/_cache',      // Директория для конфигов
    'filename' => 'locations.conf',             // Имя выходного файла
];

```

---

## Настройка Nginx

Для работы кеширования необходимо подготовить инфраструктуру на стороне Nginx.

### Глобальные настройки (`nginx.conf`)

Добавьте настройки зоны кеша и GeoIP в блок `http`:

```nginx
http {
    
    # ...
    
    # Add GeoIP2 (for SimpleWithCountryIsoCache)
    geoip2 /var/www/html/storage/app/geoip/geoip.mmdb {
        auto_reload 5m;
        $geoip2_metadata_country_build metadata build_epoch;
        $geoip2_data_country_code default=NL source=$http_x_forwarded_for country iso_code;
        $geoip2_data_country_name country names en;
    }

    # ...

    # Cache options
    proxy_cache_path /var/cache/nginx levels=2 keys_zone=httpCache:1024M inactive=12h max_size=4096M;
    proxy_cache_lock on;
    proxy_cache_methods GET HEAD POST;
    proxy_cache_min_uses 1;
    proxy_ignore_headers Expires Cache-Control;
    proxy_cache_use_stale error timeout invalid_header http_500 http_502 http_503 http_504;
    proxy_cache_revalidate on;
    proxy_cache_background_update on;
}

```

### Настройка виртуального хоста

Подключите сгенерированный файл внутри блока `server`:

```nginx
server {
    # ...
    
    set $backend http://127.0.0.1:8080;
    proxy_cache httpCache;

    # Add cache locations config
    include /etc/nginx/conf.d/_cache/locations.conf;

    location / {
        try_files $uri $uri/ @backend;
    }

    location @backend {
        internal;
        proxy_pass $backend$suffix;
    }
    
    # ...
}
```

---

## Использование

Просто добавьте атрибут к методу вашего контроллера:

```php
use Pavloniym\NginxCache\Attributes\NginxCache;
use Pavloniym\NginxCache\Types\SimpleCache;

class ProductController extends Controller
{
    #[NginxCache(type: SimpleCache::class, duration: 300)]
    public function index()
    {
        return Product::all();
    }
}

```

---

## Типы кеша

* **SimpleCache**: Базовый кеш по URI и телу запроса.
* **SimpleWithCountryIsoCache**: Кеш, разделенный по странам (требует GeoIP2).
* **SimpleWithIpCache**: Персонализированный кеш по IP адресу.
* **UserCache**: Кеш для авторизованных пользователей (учитывает сессии и Bearer токены).
* **WithoutCache**: Явное отключение кеширования для метода.

---

## Команды

**Просмотр списка всех кешируемых роутов:**

```bash
php artisan nginx-cache:list

```

**Генерация файла конфигурации:**

```bash
php artisan nginx-cache:build

```

---

## Кастомные типы кеша

Вы можете создавать свои правила, наследуя класс `NginxCacheType`:

```php
namespace App\NginxCache;

use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;

class HeavyCache extends NginxCacheType
{
    public string $key = '$request_uri';
    public string $duration = '3600s';
    public string $responses = '200';
}

```

---

## Интеграция в CI/CD

Для автоматизации обновления правил добавьте выполнение команды в процесс деплоя:

```bash
php artisan nginx-cache:build
nginx -s reload

```

## Требования

* PHP 8.1+
* Laravel 9.x / 10.x / 11.x / 12.x
* Nginx с модулем `ngx_http_geoip2_module` (для соответствующих типов кеша)

## License

MIT

---

**Важно:** Пакет только генерирует конфиги. Настройку базовых параметров Nginx (proxy_cache_path, upstream и т.д.) делай вручную в основном конфиге.