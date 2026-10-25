<?php

declare(strict_types=1);

namespace JordanPartridge\GithubClient\Cache;

use Saloon\Http\Request;
use Saloon\Http\Response;

interface CacheStore
{
    /**
     * Determine if an item is cached.
     */
    public function has(string $key): bool;

    /**
     * Get an item from the cache.
     */
    public function get(string $key): ?string;

    /**
     * Store an item in the cache.
     *
     * @param int $ttl Time to live in seconds
     */
    public function put(string $key, string $value, int $ttl): void;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): void;

    /**
     * Clear all cache items.
     */
    public function flush(): void;
}
