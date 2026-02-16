# Laravel Nginx Cache

Laravel-пакет для автоматической генерации конфигов кеширования Nginx на основе атрибутов в контроллерах.

## Зачем это нужно

Вместо того, чтобы вручную прописывать location-блоки для кеширования API-endpoints в конфигах Nginx, пакет сканирует твои Laravel-контроллеры и генерирует конфиг автоматически. Просто вешаешь атрибут `#[NginxCache]` на метод контроллера — и готово.

## Установка

```bash
composer require pavloniym/nginx-cache
```

Опубликуй конфиг:

```bash
php artisan vendor:publish --tag=config
```

В `config/nginx-cache.php`:

```php
return [
    'path' => '/etc/nginx/conf.d/_cache',      // Путь для генерации конфигов
    'filename' => 'locations.conf',             // Имя файла конфига
];
```

## Использование

### Базовый пример

```php
use Pavloniym\NginxCache\Attributes\NginxCache;
use Pavloniym\NginxCache\Types\SimpleCache;
use Pavloniym\NginxCache\Types\UserCache;
use Pavloniym\NginxCache\Types\WithoutCache;

class ProductController extends Controller
{
    #[NginxCache(type: SimpleCache::class)]
    public function index()
    {
        return Product::all();
    }
    
    #[NginxCache(
        type: SimpleCache::class,
        duration: 300,
        responses: '200 404'
    )]
    public function show(Product $product)
    {
        return $product;
    }
    
    #[NginxCache(type: UserCache::class)]
    public function profile(Request $request)
    {
        // Кеш привязан к пользователю через cookie/authorization
        return $request->user();
    }
    
    #[NginxCache(type: WithoutCache::class)]
    public function store(Request $request)
    {
        // Без кеша
    }
}
```

### Встроенные типы кеша

**SimpleCache** — базовое кеширование
- Ключ: `$request_uri|$request_method|$request_body`
- Длительность: 60s
- Ответы: any

**SimpleWithCountryIsoCache** — кеш с учётом страны пользователя
- Ключ: `$host|$request_uri|$request_method|$request_body|$geoip2_data_country_code`
- Длительность: 60s
- Требует настроенный GeoIP2 модуль в Nginx [ngx_http_geoip2_module](https://github.com/leev/ngx_http_geoip2_module)
- Пример конфига:
```nginx
http {
    geoip2 /usr/share/GeoIP/GeoLite2-Country.mmdb {
        auto_reload 5m;
        $geoip2_data_country_code country iso_code;
    }
}
```

**SimpleWithIpCache** — кеш с учётом IP
- Ключ: `$host|$request_uri|$request_method|$request_body|$http_x_forwarded_for|$remote_addr`
- Длительность: 60s
- Полезно для rate limiting или персонализированного контента
- Убедись, что `$http_x_forwarded_for` прокидывается корректно, если используешь proxy/load balancer

**UserCache** — кеш для авторизованных пользователей
- Ключ: `$host|$request_uri|$request_method|$request_body|$cookie_{session_name}|$http_authorization`
- Длительность: 1s (быстрое инвалидирование)
- Использует Laravel session cookie из конфига
- Учитывает Authorization header для API токенов

**WithoutCache** — отключение кеша
- Не генерирует location-блоки
- Используй для эндпоинтов, которые не должны кешироваться

### Параметры атрибута

- `type` — класс типа кеша (обязательный), например `SimpleCache::class`
- `duration` — время жизни кеша в секундах (опционально, переопределяет дефолт типа)
- `responses` — какие HTTP-коды кешировать (опционально, по умолчанию `any`)
- `key` — кастомный ключ кеша (опционально, переопределяет дефолт типа)
- `location` — переопределить location-паттерн (опционально, берётся из роута)

### Команды

**Посмотреть список роутов с кешем:**

```bash
php artisan nginx-cache:list
```

Покажет таблицу со всеми API-роутами, их настройками кеша и генерируемыми location-блоками.

**Сгенерировать конфиг для Nginx:**

```bash
php artisan nginx-cache:build
```

Создаёт файл с location-блоками в `/etc/nginx/conf.d/_cache/locations.conf` (или в той директории, что указана в конфиге).

### Пример сгенерированного конфига

```nginx
location = /api/products { 
    proxy_cache_key "$request_uri|$request_method|$request_body"; 
    proxy_cache_valid any 60s; 
    proxy_pass $backend; 
}
location = /api/products/ { 
    proxy_cache_key "$request_uri|$request_method|$request_body"; 
    proxy_cache_valid any 60s; 
    proxy_pass $backend; 
}

location ~ /api/products/.* { 
    proxy_cache_key "$request_uri|$request_method|$request_body"; 
    proxy_cache_valid 200 404 300s; 
    proxy_pass $backend; 
}
location ~ /api/products/.*/ { 
    proxy_cache_key "$request_uri|$request_method|$request_body"; 
    proxy_cache_valid 200 404 300s; 
    proxy_pass $backend; 
}
```

Обрати внимание: пути с параметрами (типа `/api/products/{id}`) автоматически конвертируются в regex-паттерны (`/api/products/.*`).

## Кастомные типы кеша

Создай свой тип, унаследовав `NginxCacheType`:

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

Используй напрямую:

```php
use App\NginxCache\HeavyCache;

#[NginxCache(type: HeavyCache::class)]
public function heavyEndpoint() { ... }
```

### Динамические ключи кеша

Можно генерировать ключ динамически, используя Laravel конфиг:

```php
class ApiTokenCache extends NginxCacheType
{
    public string $duration = '30s';
    public string $responses = 'any';

    public function getKey(): string
    {
        $tokenHeader = config('sanctum.token_header', 'Authorization');
        return sprintf('$host|$request_uri|$http_%s', strtolower($tokenHeader));
    }
}
```

Так работает `UserCache` — он тянет имя session cookie из конфига Laravel.

## Интеграция в CI/CD

Добавь в свой deploy-pipeline:

```bash
php artisan nginx-cache:build
nginx -s reload
```

Теперь при каждом деплое конфиги будут перегенерироваться автоматически.

## Как это работает

1. Пакет сканирует все роуты, зарегистрированные в приложении
2. Через рефлексию читает атрибуты `#[NginxCache]` у методов контроллеров
3. Генерирует location-блоки для Nginx на основе этих атрибутов
4. Сохраняет их в указанный файл

## Требования

- PHP 8.1+
- Laravel 9.x+
- Nginx с настроенным proxy_cache

## License

MIT

---

**Важно:** Пакет только генерирует конфиги. Настройку базовых параметров Nginx (proxy_cache_path, upstream и т.д.) делай вручную в основном конфиге.