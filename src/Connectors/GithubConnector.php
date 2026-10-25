<?php

namespace JordanPartridge\GithubClient\Connectors;

use JordanPartridge\GithubClient\Auth\AuthenticationStrategy;
use JordanPartridge\GithubClient\Auth\GitHubAppAuthentication;
use JordanPartridge\GithubClient\Auth\TokenResolver;
use JordanPartridge\GithubClient\Exceptions\ApiException;
use JordanPartridge\GithubClient\Exceptions\AuthenticationException;
use JordanPartridge\GithubClient\Exceptions\NetworkException;
use JordanPartridge\GithubClient\Exceptions\RateLimitException;
use JordanPartridge\GithubClient\Exceptions\ResourceNotFoundException;
use JordanPartridge\GithubClient\Exceptions\ValidationException;
use JordanPartridge\GithubClient\Logging\GithubLogger;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

/**
 * GitHub API connector with optional authentication support.
 *
 * This connector supports:
 * - Optional authentication (public repos don't need auth)
 * - Multiple authentication sources (CLI, env, config)
 * - Helpful error messages for rate limits
 * - Context-aware exception handling
 */
class GithubConnector extends Connector
{
    use AcceptsJson;

    protected ?string $token;
    protected ?string $tokenSource;
    protected ?AuthenticationStrategy $authStrategy = null;
    protected ?GithubLogger $logger = null;

    /**
     * Create a new GitHub connector.
     *
     * @param  string|AuthenticationStrategy|null  $token  Token, auth strategy, or null to auto-resolve
     */
    public function __construct(
        string|AuthenticationStrategy|null $token = null,
        ?GithubLogger $logger = null
    ) {
        if ($token instanceof AuthenticationStrategy) {
            $this->authStrategy = $token;
            $this->token = null;
            $this->tokenSource = $token->getType();

            // Inject connector into auth strategy if it's a GitHub App
            if ($token instanceof GitHubAppAuthentication) {
                $token->setConnector($this);
            }
        } elseif ($token !== null) {
            $this->token = $token;
            $this->tokenSource = 'explicit';
        } else {
            // Try to resolve token from multiple sources
            $this->token = TokenResolver::resolve();
            $this->tokenSource = $this->token ? TokenResolver::getLastSource() : null;
        }

        $this->logger = $logger ?? new GithubLogger();
    }

    /**
     * The GitHub API base URL.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }

    /**
     * Default headers for all requests.
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    /**
     * Configure authentication if available.
     * Returns null for unauthenticated requests (public repos).
     */
    protected function defaultAuth(): ?Authenticator
    {
        // Use auth strategy if set
        if ($this->authStrategy) {
            // Check if token needs refresh
            if ($this->authStrategy->needsRefresh()) {
                $this->refreshAuthToken();
            }

            // Get the authorization header value
            $authHeader = $this->authStrategy->getAuthorizationHeader();

            // Extract token from "Bearer <token>" format
            $token = str_replace('Bearer ', '', $authHeader);

            return new TokenAuthenticator($token);
        }

        // Fall back to simple token authentication
        if (! $this->token) {
            return null;
        }

        return new TokenAuthenticator($this->token);
    }

    /**
     * Check if the connector is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->authStrategy !== null || ! empty($this->token);
    }

    /**
     * Get the authentication source.
     */
    public function getAuthenticationSource(): ?string
    {
        return $this->tokenSource;
    }

    /**
     * Get authentication status for debugging.
     */
    public function getAuthenticationStatus(): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'authenticated' => false,
                'source' => null,
                'message' => 'No authentication configured. Using public API access (60 requests/hour).',
            ];
        }

        return [
            'authenticated' => true,
            'source' => $this->tokenSource,
            'token_preview' => $this->token ? substr($this->token, 0, 7) . '...' : null,
            'message' => "Authenticated via {$this->tokenSource} (5,000 requests/hour).",
        ];
    }

    /**
     * Determine if a request has failed.
     */
    public function hasRequestFailed(Response $response): bool
    {
        return $response->failed();
    }

    /**
     * Map response status codes to appropriate exceptions.
     */
    public function getRequestException(Response $response, ?\Throwable $senderException = null): ?\Throwable
    {
        $status = $response->status();
        $data = $response->json();
        $message = $data['message'] ?? '';

        return match ($status) {
            401 => $this->handleAuthenticationError($response, $message),
            403 => $this->handleForbiddenError($response, $message),
            404 => new ResourceNotFoundException(
                message: $message ?: 'GitHub resource not found',
                response: $response,
            ),
            422 => $this->handleValidationError($response, $data),
            429 => $this->handleRateLimitError($response, $message),
            500, 502, 503, 504 => new NetworkException(
                operation: 'GitHub API request',
                message: "Server error ({$status}): {$message}",
                previous: $senderException,
            ),
            default => new ApiException(
                response: $response,
                message: $message ?: "Unexpected error (HTTP {$status})",
                previous: $senderException,
            ),
        };
    }

    /**
     * Handle 401 authentication errors.
     */
    protected function handleAuthenticationError(Response $response, string $message): AuthenticationException
    {
        $helpMessage = $message ?: 'GitHub authentication failed';

        if (! $this->isAuthenticated()) {
            $helpMessage .= "\n\nThis endpoint requires authentication. ";
            $helpMessage .= TokenResolver::getAuthenticationHelp();
        } else {
            $helpMessage .= "\n\nYour token may be invalid or expired.";
            $helpMessage .= "\nCurrent auth source: {$this->tokenSource}";
        }

        return new AuthenticationException(
            message: $helpMessage,
            authenticationType: $this->tokenSource ?? 'none',
        );
    }

    /**
     * Handle 403 forbidden errors (could be rate limit or permissions).
     */
    protected function handleForbiddenError(Response $response, string $message): \Throwable
    {
        // Check if this is a rate limit issue
        if ($this->isRateLimited($response, $message)) {
            return $this->handleRateLimitError($response, $message);
        }

        // Otherwise it's a permissions issue
        $helpMessage = $message ?: 'Access to this GitHub resource is forbidden';

        if ($this->isAuthenticated()) {
            $helpMessage .= "\n\nYour token may not have the required scopes for this operation.";
            $helpMessage .= "\nCheck that your token has the necessary permissions.";
        } else {
            $helpMessage .= "\n\nThis resource may require authentication.";
            $helpMessage .= "\n" . TokenResolver::getAuthenticationHelp();
        }

        return new ApiException(
            response: $response,
            message: $helpMessage,
        );
    }

    /**
     * Check if a 403 response is due to rate limiting.
     */
    protected function isRateLimited(Response $response, string $message): bool
    {
        // Check message for rate limit text
        if (stripos($message, 'rate limit') !== false) {
            return true;
        }

        // Check headers for rate limit
        $remaining = $response->header('X-RateLimit-Remaining');
        if ($remaining !== null && (int) $remaining === 0) {
            return true;
        }

        return false;
    }

    /**
     * Handle rate limit errors with helpful context.
     */
    protected function handleRateLimitError(Response $response, string $message): RateLimitException
    {
        $headers = $response->headers();
        $remaining = (int) ($headers->get('X-RateLimit-Remaining') ?? 0);
        $limit = (int) ($headers->get('X-RateLimit-Limit') ?? ($this->isAuthenticated() ? 5000 : 60));
        $reset = $headers->get('X-RateLimit-Reset');
        $resetTime = $reset ? new \DateTimeImmutable('@' . $reset) : new \DateTimeImmutable('+1 hour');

        // Build helpful message
        $helpMessage = $message ?: 'GitHub API rate limit exceeded';

        if (! $this->isAuthenticated()) {
            $helpMessage .= "\n\nYou're using unauthenticated requests (60/hour limit).";
            $helpMessage .= "\n" . TokenResolver::getAuthenticationHelp();
        } else {
            $helpMessage .= "\n\nAuthenticated via: {$this->tokenSource}";
            $helpMessage .= "\nRate limit: {$remaining}/{$limit} requests remaining";
            $helpMessage .= "\nResets at: " . $resetTime->format('Y-m-d H:i:s T');
            $helpMessage .= "\n\nConsider implementing caching or reducing API calls.";
        }

        return new RateLimitException(
            remainingRequests: $remaining,
            resetTime: $resetTime,
            totalLimit: $limit,
            message: $helpMessage,
        );
    }

    /**
     * Handle validation errors with field-specific details.
     */
    protected function handleValidationError(Response $response, array $data): ValidationException
    {
        $message = $data['message'] ?? 'Validation failed';
        $errors = $data['errors'] ?? [];

        // Build detailed error message
        if (! empty($errors)) {
            $message .= "\n\nValidation errors:";
            foreach ($errors as $error) {
                $field = $error['field'] ?? 'unknown';
                $code = $error['code'] ?? 'invalid';
                $resource = $error['resource'] ?? '';
                $message .= "\n  - {$field}: {$code}";
                if ($resource) {
                    $message .= " (resource: {$resource})";
                }
            }
        }

        return new ValidationException(
            message: $message,
            field: $errors[0]['field'] ?? null,
            value: $errors[0]['value'] ?? null,
        );
    }

    /**
     * Send a request with event logging.
     *
     * This wraps the parent send method to log request start, completion, and error events.
     * Request/response bodies are not logged to avoid logging sensitive data.
     *
     * @param  Request  $request  The request to send
     * @param  MockClient|null  $mockClient  Optional mock client
     * @param  callable|null  $handleRetry  Optional retry handler
     * @return Response The response from the API
     */
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        $startTime = microtime(true);
        $requestClass = $request::class;

        // Log request start (no sensitive data here)
        $this->logger->logRequestStart($requestClass, [
            'endpoint' => $request->resolveEndpoint(),
            'method' => $request->getMethod()->value,
        ]);

        try {
            $response = parent::send($request, $mockClient, $handleRetry);
            $duration = microtime(true) - $startTime;

            // Log request completion (status only, no body)
            $this->logger->logRequestComplete($requestClass, $response->status(), $duration, [
                'endpoint' => $request->resolveEndpoint(),
                'status' => $response->status(),
            ]);

            return $response;
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;

            // Log request error (exception details only, no request body)
            $this->logger->logRequestError($requestClass, $exception, [
                'endpoint' => $request->resolveEndpoint(),
                'duration_seconds' => $duration,
            ]);

            throw $exception;
        }
    }

    /**
     * Refresh authentication token with event logging.
     *
     * @return void
     */
    public function refreshAuthToken(): void
    {
        if (! $this->authStrategy) {
            return;
        }

        $startTime = microtime(true);
        $strategyClass = $this->authStrategy::class;

        // Log authentication refresh start
        $this->logger->logAuthenticationRefresh($strategyClass, [
            'action' => 'refresh_started',
        ]);

        try {
            $this->authStrategy->refresh();
            $duration = microtime(true) - $startTime;

            // Log successful refresh
            $this->logger->logAuthenticationRefresh($strategyClass, [
                'action' => 'refresh_completed',
                'duration_seconds' => $duration,
            ]);
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;

            // Log refresh error
            $this->logger->logAuthenticationRefresh($strategyClass, [
                'action' => 'refresh_failed',
                'duration_seconds' => $duration,
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }

    /**
     * Get the logger instance.
     */
    public function getLogger(): GithubLogger
    {
        return $this->logger ?? new GithubLogger();
    }

    /**
     * Set the logger instance.
     */
    public function setLogger(GithubLogger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }
}
