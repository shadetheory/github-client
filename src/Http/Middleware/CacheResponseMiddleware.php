<?php

declare(strict_types=1);

namespace JordanPartridge\GithubClient\Http\Middleware;

use JordanPartridge\GithubClient\Cache\CacheKeyGenerator;
use JordanPartridge\GithubClient\Cache\CacheStore;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Http\Faking\MockResponse;

class CacheResponseMiddleware
{
    /**
     * The cache store instance.
     */
    protected CacheStore $cacheStore;

    /**
     * Default TTL in seconds (1 hour).
     */
    protected int $defaultTtl;

    /**
     * Create a new CacheResponseMiddleware instance.
     */
    public function __construct(
        CacheStore $cacheStore,
        int $defaultTtl = 3600,
    ) {
        $this->cacheStore = $cacheStore;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Handle the request middleware.
     */
    public function onRequest(PendingRequest $pendingRequest): PendingRequest
    {
        // Only cache GET requests
        if ($pendingRequest->getMethod()->value !== 'GET') {
            return $pendingRequest;
        }

        // Check if caching is explicitly disabled on the request
        if ($pendingRequest->getRequest()->hasCacheDisabled()) {
            return $pendingRequest;
        }

        $cacheKey = CacheKeyGenerator::forRequest($pendingRequest->getRequest(), $pendingRequest->getQuery());

        // Check if we have a cached response
        if ($this->cacheStore->has($cacheKey)) {
            $cachedResponse = $this->cacheStore->get($cacheKey);

            // Create a mock response from the cached data
            $pendingRequest->setFakeResponse(new class ($cachedResponse) extends MockResponse {
                private string $content;

                public function __construct(string $content)
                {
                    $this->content = $content;
                    parent::__construct(json_decode($content, true), 200);
                }

                public function json(?string $key = null, mixed $default = null): mixed
                {
                    $data = json_decode($this->content, true);

                    if ($key === null) {
                        return $data;
                    }

                    return data_get($data, $key, $default);
                }

                public function body(): string
                {
                    return $this->content;
                }
            });
        }

        return $pendingRequest;
    }

    /**
     * Handle the response middleware.
     */
    public function onResponse(Response $response): Response
    {
        // Only cache successful GET responses
        if ($response->getRequest()->getMethod()->value !== 'GET') {
            return $response;
        }

        if (! $response->successful()) {
            return $response;
        }

        // Check if caching is explicitly disabled
        if ($response->getRequest()->hasCacheDisabled()) {
            return $response;
        }

        $cacheKey = CacheKeyGenerator::forRequest($response->getRequest(), $response->getRequest()->getQuery());

        // Get TTL from config or use default
        $ttl = $response->getRequest()->getCacheTtl() ?? $this->defaultTtl;

        // Cache the response
        $this->cacheStore->put($cacheKey, $response->body(), $ttl);

        return $response;
    }
}
