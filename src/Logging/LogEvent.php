<?php

namespace JordanPartridge\GithubClient\Logging;

/**
 * Enum representing logging events for GitHub API requests.
 *
 * These events are logged without request/response bodies to avoid
 * logging sensitive data or large payloads.
 */
enum LogEvent: string
{
    case REQUEST_START = 'github.request.start';
    case REQUEST_COMPLETE = 'github.request.complete';
    case REQUEST_ERROR = 'github.request.error';
    case AUTHENTICATION_REFRESH = 'github.auth.refresh';
}
