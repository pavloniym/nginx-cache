<?php

declare(strict_types=1);

namespace Pavloniym\NginxCache\Attributes;

use Attribute;
use Illuminate\Routing\Route;
use Pavloniym\NginxCache\Contracts\Types\NginxCacheType;
use Pavloniym\NginxCache\Enums\NginxCacheTypeEnum;

#[Attribute(Attribute::TARGET_METHOD)]
class NginxCache
{
    public ?Route $route = null;
    public ?NginxCacheType $cacheType = null;

    /**
     * @param NginxCacheTypeEnum $type
     * @param string|null $location
     * @param string|null $key
     * @param string|null $responses
     * @param int|null $duration
     */
    public function __construct(
        public NginxCacheTypeEnum $type,
        public ?string            $key = null,
        public ?string            $location = null,
        public ?int               $duration = null,
        public ?string            $responses = null,
    )
    {
        $this->cacheType = $type->getNginxCacheTypeInstance();
    }

    /**
     * @param Route $route
     * @return $this
     */
    public function setRoute(Route $route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->isExactLocation()
            ? sprintf('= %s', $this->getRawLocation())
            : sprintf('~ %s', $this->getRawLocation());
    }

    /**
     * @return string|null
     */
    public function getRawLocation(): ?string
    {
        return sprintf('/%s', trim($this->replaceLocationPathParameters(location: $this->location ?? $this->route?->uri()), '/'));
    }

    /**
     * @return bool
     */
    public function isExactLocation(): bool
    {
        return str_contains($this->getRawLocation(), '.*') === false;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key ?? $this->cacheType?->getKey() ?? null;
    }

    /**
     * @return string|null
     */
    public function getDuration(): ?string
    {
        return sprintf('%ss', rtrim($this->duration ? (string)$this->duration : ($this->cacheType?->getDuration() ?? ''), 's'));
    }

    /**
     * @return string|null
     */
    public function getResponses(): ?string
    {
        return $this->responses ?? $this->cacheType?->getResponses();
    }

    /**
     * @return string|null
     */
    public function getConfiguration(): ?string
    {
        return $this->cacheType?->getConfiguration(nginxCache: $this);
    }


    /**
     * Replace parameters of path to wildcard
     * Regular expression to match {...} and replace with (.*)
     * Perform the replacement
     *
     * @param string $location
     * @return array|string|string[]|null
     */
    private function replaceLocationPathParameters(string $location): array|string|null
    {
        return preg_replace('/\{.*?\}/', '.*', $location);
    }
}
