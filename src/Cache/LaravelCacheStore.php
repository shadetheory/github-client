<?php

declare(strict_types=1);

namespace JordanPartridge\GithubClient\Cache;

use Illuminate\Cache\Repository as CacheRepository;

class LaravelCacheStore implements CacheStore
{
    /**
     * The cache repository instance.
     */
    protected CacheRepository $cache;

    /**
     * The cache prefix.
     */
    protected string $prefix;

    /**
     * Default TTL in seconds (1 hour).
     */
    protected int $defaultTtl;

    /**
     * Create a new Laravel cache store.
     *
     * @param  CacheRepository  $cache  The cache repository
     * @param  string  $prefix  Cache key prefix
     * @param  int  $defaultTtl  Default TTL in seconds
     */
    public function __construct(
        CacheRepository $cache,
        string $prefix = 'github-client:',
        int $defaultTtl = 3600
    ) {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Get the full cache key with prefix.
     */
    protected function getFullKey(string $key): string
    {
        return $this->prefix.$key;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return $this->cache->has($this->getFullKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?string
    {
        return $this->cache->get($this->getFullKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $key, string $value, int $ttl): void
    {
        $this->cache->put($this->getFullKey($key), $value, $ttl > 0 ? $ttl : $this->defaultTtl);
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $key): void
    {
        $this->cache->forget($this->getFullKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function flush(): void
    {
        $this->cache->flush();
    }
}
