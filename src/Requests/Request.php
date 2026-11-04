<?php

namespace JordanPartridge\GithubClient\Requests;

use Saloon\Http\Request as SaloonRequest;
use JordanPartridge\GithubClient\Data\RetryConfig;

/**
 * Base request class with GitHub-specific functionality.
 *
 * This extends Saloon's Request with retry handling for rate limits.
 */
abstract class Request extends SaloonRequest
{
    /**
     * Determine if the request should be retried after a failure.
     *
     * @param \Saloon\Exceptions\Request\FatalRequestException|\Saloon\Exceptions\Request\RequestException $exception
     * @param \Saloon\Http\Request $request
     * @return bool
     */
    public function handleRetry(\Saloon\Exceptions\Request\FatalRequestException|\Saloon\Exceptions\Request\RequestException $exception, \Saloon\Http\Request $request): bool
    {
        $retryConfig = new \JordanPartridge\GithubClient\Data\RetryConfig(config('github-client.retry', []));

        // Get the response from the exception
        $response = $exception->getResponse();

        // Check if the response status should trigger a retry
        if (! in_array($response->status(), $retryConfig->onlyOn)) {
            return false;
        }

        return true;
    }
}
