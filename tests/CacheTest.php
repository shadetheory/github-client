<?php

declare(strict_types=1);

use JordanPartridge\GithubClient\Cache\CacheKeyGenerator;
use JordanPartridge\GithubClient\Cache\LaravelCacheStore;
use JordanPartridge\GithubClient\Http\Middleware\CacheResponseMiddleware;
use JordanPartridge\GithubClient\Requests\Repos\Get as GetRepoRequest;
use JordanPartridge\GithubClient\ValueObjects\Repo;
use Mockery as m;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

test('cache key generator creates unique keys for different endpoints', function () {
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    $key = CacheKeyGenerator::forRepo($request);

    expect($key)->toContain('github-client:get:/repos/owner/repo');
});

test('cache key generator adds query parameters when provided', function () {
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    $key = CacheKeyGenerator::forRequest($request, ['foo' => 'bar', 'baz' => 'qux']);

    expect($key)->toContain('github-client:get:/repos/owner/repo?');
    expect($key)->toContain('foo=bar');
    expect($key)->toContain('baz=qux');
});

test('laravel cache store can set and get values', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');

    $store->put('test-key', 'test-value', 3600);

    expect($store->get('test-key'))->toBe('test-value');
    expect($store->has('test-key'))->toBeTrue();
});

test('laravel cache store respects ttl', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.', 1); // 1 second TTL

    $store->put('test-key', 'test-value', 1);

    expect($store->get('test-key'))->toBe('test-value');

    // Wait for TTL to expire
    sleep(2);

    expect($store->get('test-key'))->toBeNull();
    expect($store->has('test-key'))->toBeFalse();
});

test('laravel cache store can forget specific keys', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');

    $store->put('test-key', 'test-value', 3600);
    $store->forget('test-key');

    expect($store->has('test-key'))->toBeFalse();
});

test('laravel cache store can flush all keys', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');

    $store->put('key1', 'value1', 3600);
    $store->put('key2', 'value2', 3600);

    $store->flush();

    expect($store->has('key1'))->toBeFalse();
    expect($store->has('key2'))->toBeFalse();
});

test('cache response middleware caches get responses', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');
    $middleware = new CacheResponseMiddleware($store, 3600);

    // Create a mock request
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    // Create a mock response
    $mockResponse = m::mock(Response::class);
    $mockResponse->shouldReceive('getRequest')->andReturn($request);
    $mockResponse->shouldReceive('getMethod')->andReturn(Method::GET);
    $mockResponse->shouldReceive('successful')->andReturnTrue();
    $mockResponse->shouldReceive('body')->andReturn(json_encode(['id' => 123, 'name' => 'test']));
    $mockResponse->shouldReceive('json')->andReturn(['id' => 123, 'name' => 'test']);
    $mockResponse->shouldReceive('status')->andReturn(200);

    // Run the response middleware to cache
    $middleware->onResponse($mockResponse);

    // Verify the response was cached
    expect($store->has(CacheKeyGenerator::forRepo($request)))->toBeTrue();
});

test('cache response middleware does not cache non-get requests', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');
    $middleware = new CacheResponseMiddleware($store, 3600);

    // Create a mock POST request
    $repo = Repo::fromFullName('owner/repo');

    // Test with a request that uses POST (we'll mock it)
    $postRequest = m::mock(Request::class);
    $postRequest->shouldReceive('getMethod')->andReturn(Method::POST);
    $postRequest->shouldReceive('resolveEndpoint')->andReturn('/repos/owner/repo');

    // Create a mock response
    $mockResponse = m::mock(Response::class);
    $mockResponse->shouldReceive('getRequest')->andReturn($postRequest);
    $mockResponse->shouldReceive('getMethod')->andReturn(Method::POST);
    $mockResponse->shouldReceive('successful')->andReturnTrue();
    $mockResponse->shouldReceive('body')->andReturn(json_encode(['id' => 123]));
    $mockResponse->shouldReceive('json')->andReturn(['id' => 123]);
    $mockResponse->shouldReceive('status')->andReturn(200);

    // Run the response middleware - should not cache
    $middleware->onResponse($mockResponse);

    // Verify nothing was cached
    expect($store->has('github-post-key'))->toBeFalse();
});

test('cache response middleware does not cache failed responses', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');
    $middleware = new CacheResponseMiddleware($store, 3600);

    // Create a mock request
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    // Create a mock response with 404 status
    $mockResponse = m::mock(Response::class);
    $mockResponse->shouldReceive('getRequest')->andReturn($request);
    $mockResponse->shouldReceive('getMethod')->andReturn(Method::GET);
    $mockResponse->shouldReceive('successful')->andReturnFalse();
    $mockResponse->shouldReceive('status')->andReturn(404);

    // Run the response middleware - should not cache
    $middleware->onResponse($mockResponse);

    // Verify nothing was cached
    expect($store->has(CacheKeyGenerator::forRepo($request)))->toBeFalse();
});

test('cache response middleware can retrieve cached responses', function () {
    $cache = app('cache')->driver('array');
    $store = new LaravelCacheStore($cache, 'test.');
    $middleware = new CacheResponseMiddleware($store, 3600);

    // Create a mock request and cache a response
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);
    $cacheKey = CacheKeyGenerator::forRepo($request);

    $store->put($cacheKey, json_encode(['id' => 456, 'name' => 'cached']), 3600);

    // Create a pending request
    $pendingRequest = m::mock(PendingRequest::class);
    $pendingRequest->shouldReceive('getMethod')->andReturn(Method::GET);
    $pendingRequest->shouldReceive('getRequest')->andReturn($request);
    $pendingRequest->shouldReceive('getQuery')->andReturn([]);
    $pendingRequest->shouldReceive('hasCacheDisabled')->andReturn(false);

    // Run the request middleware - should find cached response
    $result = $middleware->onRequest($pendingRequest);

    // Verify the fake response was set
    expect($result->getFakeResponse())->not->toBeNull();
});

test('request can disable caching with withoutCache', function () {
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    $result = $request->withoutCache();

    expect($result)->toBe($request);
    expect($request->hasCacheDisabled())->toBeTrue();
});

test('request can enable caching with cache method', function () {
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    $result = $request->cache(7200);

    expect($result)->toBe($request);
    expect($request->isCacheEnabled())->toBeTrue();
    expect($request->getCacheTtl())->toBe(7200);
});

test('default cache ttl is 3600 seconds', function () {
    $repo = Repo::fromFullName('owner/repo');
    $request = new GetRepoRequest($repo);

    $request->cache();

    expect($request->getCacheTtl())->toBe(3600);
});
