<?php

declare(strict_types=1);

namespace JordanPartridge\GithubClient\Traits;

/**
 * @mixin \Saloon\Http\Request
 */
trait HasCacheSettings
{
    /**
     * Whether caching is enabled for this request.
     */
    protected bool $cacheEnabled = false;

    /**
     * TTL in seconds for cached responses. Default 1 hour.
     */
    protected int $cacheTtl = 3600;

    /**
     * Enable caching for this request.
     *
     * @param  int|null  $ttl  Time to live in seconds. If null, uses default TTL.
     */
    public function cache(?int $ttl = null): self
    {
        $this->cacheEnabled = true;

        if ($ttl !== null) {
            $this->cacheTtl = $ttl;
        }

        return $this;
    }

    /**
     * Disable caching for this request.
     */
    public function withoutCache(): self
    {
        $this->cacheEnabled = false;

        return $this;
    }

    /**
     * Check if caching is enabled for this request.
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Get the cache TTL in seconds.
     */
    public function getCacheTtl(): ?int
    {
        return $this->cacheEnabled ? $this->cacheTtl : null;
    }

    /**
     * Check if caching is explicitly disabled (via withoutCache()).
     */
    public function hasCacheDisabled(): bool
    {
        return ! $this->cacheEnabled;
    }
}
