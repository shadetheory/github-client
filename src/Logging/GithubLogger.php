<?php

namespace JordanPartridge\GithubClient\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * PSR-3 logger wrapper for the GitHub client.
 *
 * This logger only logs event metadata, not request/response bodies
 * to avoid logging sensitive data or large payloads.
 */
class GithubLogger
{
    protected LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Log a request start event.
     *
     * @param  class-string  $requestClass  The request class name
     * @param  array<string, mixed>  $context  Additional context
     */
    public function logRequestStart(string $requestClass, array $context = []): void
    {
        $this->logger->info(
            'Starting GitHub API request',
            [
                'event' => LogEvent::REQUEST_START->value,
                'request_class' => $requestClass,
                ...$context,
            ],
        );
    }

    /**
     * Log a request completion event.
     *
     * @param  class-string  $requestClass  The request class name
     * @param  int  $statusCode  HTTP status code
     * @param  float  $duration  Request duration in seconds
     * @param  array<string, mixed>  $context  Additional context
     */
    public function logRequestComplete(string $requestClass, int $statusCode, float $duration, array $context = []): void
    {
        $this->logger->info(
            'GitHub API request completed',
            [
                'event' => LogEvent::REQUEST_COMPLETE->value,
                'request_class' => $requestClass,
                'status_code' => $statusCode,
                'duration_seconds' => $duration,
                ...$context,
            ],
        );
    }

    /**
     * Log a request error event.
     *
     * @param  class-string  $requestClass  The request class name
     * @param  \Throwable  $exception  The exception that occurred
     * @param  array<string, mixed>  $context  Additional context
     */
    public function logRequestError(string $requestClass, \Throwable $exception, array $context = []): void
    {
        $this->logger->error(
            'GitHub API request failed',
            [
                'event' => LogEvent::REQUEST_ERROR->value,
                'request_class' => $requestClass,
                'exception_class' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
                ...$context,
            ],
        );
    }

    /**
     * Log an authentication refresh event.
     *
     * @param  string  $strategy  The authentication strategy type
     * @param  array<string, mixed>  $context  Additional context
     */
    public function logAuthenticationRefresh(string $strategy, array $context = []): void
    {
        $this->logger->info(
            'GitHub authentication token refreshed',
            [
                'event' => LogEvent::AUTHENTICATION_REFRESH->value,
                'strategy' => $strategy,
                ...$context,
            ],
        );
    }

    /**
     * Get the underlying PSR-3 logger.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
