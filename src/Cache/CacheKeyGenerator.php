<?php

declare(strict_types=1);

namespace JordanPartridge\GithubClient\Cache;

use JordanPartridge\GithubClient\Requests\Repos\Get as GetRepoRequest;
use Saloon\Http\Request;

class CacheKeyGenerator
{
    /**
     * Generate a cache key for a request.
     */
    public static function forRequest(Request $request, array $query = []): string
    {
        $baseKey = self::getBaseKey($request);

        if (empty($query)) {
            return $baseKey;
        }

        return $baseKey . '?' . http_build_query($query);
    }

    /**
     * Get the base cache key for a request.
     */
    public static function getBaseKey(Request $request): string
    {
        $method = $request->getMethod()->value;
        $endpoint = $request->resolveEndpoint();

        return 'github-client:' . strtolower($method) . ':' . $endpoint;
    }

    /**
     * Generate a cache key for a repository request.
     *
     * @deprecated Use forRequest() instead. The endpoint already contains repo info.
     */
    public static function forRepo(GetRepoRequest $request): string
    {
        return self::getBaseKey($request);
    }
}
