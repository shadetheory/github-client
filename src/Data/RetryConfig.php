<?php

namespace JordanPartridge\GithubClient\Data;

/**
 * Configuration for request retry behavior.
 */
class RetryConfig
{
    /**
     * Maximum number of retry attempts.
     */
    public int $maxAttempts = 3;

    /**
     * Delay in milliseconds between retries.
     */
    public int $delay = 1000;

    /**
     * HTTP status codes that should trigger a retry.
     */
    public array $onlyOn = [429];

    /**
     * Use exponential backoff?
     */
    public bool $useExponentialBackoff = false;

    public function __construct(array $config = [])
    {
        if (isset($config['max_attempts'])) {
            $this->maxAttempts = $config['max_attempts'];
        }
        if (isset($config['delay'])) {
            $this->delay = $config['delay'];
        }
        if (isset($config['only_on'])) {
            $this->onlyOn = $config['only_on'];
        }
        if (isset($config['use_exponential_backoff'])) {
            $this->useExponentialBackoff = $config['use_exponential_backoff'];
        }
    }
}
