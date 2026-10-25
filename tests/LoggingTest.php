<?php

use JordanPartridge\GithubClient\Connectors\GithubConnector;
use JordanPartridge\GithubClient\Logging\GithubLogger;
use JordanPartridge\GithubClient\Logging\LogEvent;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

describe('PSR Logger hook', function () {
    it('logs request start event', function () {
        $mockLogger = m::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')
            ->once()
            ->with(
                'Starting GitHub API request',
                m::contains([
                    'event' => LogEvent::REQUEST_START->value,
                    'request_class' => \JordanPartridge\GithubClient\Requests\Repos\Get::class,
                ])
            );

        $connector = new GithubConnector('test-token', new GithubLogger($mockLogger));

        $mockClient = new MockClient([
            MockResponse::make([
                'id' => 1,
                'node_id' => 'MDEwOlJlcG9zaXRvcnkxMjk2MjY5',
                'name' => 'test-repo',
                'full_name' => 'owner/test-repo',
                'private' => false,
                'owner' => [
                    'login' => 'owner',
                    'id' => 1,
                ],
                'html_url' => 'https://github.com/owner/test-repo',
                'description' => 'Test repo',
                'fork' => false,
                'url' => 'https://api.github.com/repos/owner/test-repo',
                'forks_url' => 'https://api.github.com/repos/owner/test-repo/forks',
                'keys_url' => 'https://api.github.com/repos/owner/test-repo/keys{/key_id}',
                'collaborators_url' => 'https://api.github.com/repos/owner/test-repo/collaborators{/collaborator}',
                'teams_url' => 'https://api.github.com/repos/owner/test-repo/teams',
                'hooks_url' => 'https://api.github.com/repos/owner/test-repo/hooks',
                'issue_events_url' => 'https://api.github.com/repos/owner/test-repo/issues/events{/number}',
                'events_url' => 'https://api.github.com/repos/owner/test-repo/events',
                'assignees_url' => 'https://api.github.com/repos/owner/test-repo/assignees{/user}',
                'branches_url' => 'https://api.github.com/repos/owner/test-repo/branches{/branch}',
                'tags_url' => 'https://api.github.com/repos/owner/test-repo/tags',
                'blobs_url' => 'https://api.github.com/repos/owner/test-repo/git/blobs{/sha}',
                'git_tags_url' => 'https://api.github.com/repos/owner/test-repo/git/tags{/sha}',
                'git_refs_url' => 'https://api.github.com/repos/owner/test-repo/git/refs{/sha}',
                'trees_url' => 'https://api.github.com/repos/owner/test-repo/git/trees{/sha}',
                'statuses_url' => 'https://api.github.com/repos/owner/test-repo/statuses/{sha}',
                'languages_url' => 'https://api.github.com/repos/owner/test-repo/languages',
                'stargazers_url' => 'https://api.github.com/repos/owner/test-repo/stargazers',
                'contributors_url' => 'https://api.github.com/repos/owner/test-repo/contributors',
                'subscribers_url' => 'https://api.github.com/repos/owner/test-repo/subscribers',
                'subscription_url' => 'https://api.github.com/repos/owner/test-repo/subscription',
                'commits_url' => 'https://api.github.com/repos/owner/test-repo/commits{/sha}',
                'git_commits_url' => 'https://api.github.com/repos/owner/test-repo/git/commits{/sha}',
                'comments_url' => 'https://api.github.com/repos/owner/test-repo/comments{/number}',
                'issue_comment_url' => 'https://api.github.com/repos/owner/test-repo/issues/comments{/number}',
                'contents_url' => 'https://api.github.com/repos/owner/test-repo/contents/{+path}',
                'compare_url' => 'https://api.github.com/repos/owner/test-repo/compare/{base}...{head}',
                'merges_url' => 'https://api.github.com/repos/owner/test-repo/merges',
                'archive_url' => 'https://api.github.com/repos/owner/test-repo/{archive_format}{/ref}',
                'downloads_url' => 'https://api.github.com/repos/owner/test-repo/downloads',
                'issues_url' => 'https://api.github.com/repos/owner/test-repo/issues{/number}',
                'pulls_url' => 'https://api.github.com/repos/owner/test-repo/pulls{/number}',
                'releases_url' => 'https://api.github.com/repos/owner/test-repo/releases{/id}',
                'deployments_url' => 'https://api.github.com/repos/owner/test-repo/deployments',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-01-01T00:00:00Z',
                'git_url' => 'git://github.com/owner/test-repo.git',
                'ssh_url' => 'git@github.com:owner/test-repo.git',
                'clone_url' => 'https://github.com/owner/test-repo.git',
                'svn_url' => 'https://github.com/owner/test-repo',
                'homepage' => null,
                'size' => 108,
                'stargazers_count' => 0,
                'watchers_count' => 0,
                'language' => null,
                'has_issues' => true,
                'has_projects' => true,
                'has_wiki' => true,
                'has_pages' => false,
                'has_downloads' => true,
                'archived' => false,
                'disabled' => false,
                'open_issues_count' => 0,
                'license' => null,
                'allow_forking' => true,
                'is_template' => false,
                'web_commit_signoff_required' => false,
                'topics' => [],
                'visibility' => 'public',
                'forks' => 0,
                'open_issues' => 0,
                'watchers' => 0,
                'default_branch' => 'main',
                'score' => 1.0,
            ], 200),
        ]);

        $connector->send(new \JordanPartridge\GithubClient\Requests\Repos\Get(\JordanPartridge\GithubClient\ValueObjects\Repo::fromFullName('owner/test-repo')), $mockClient);
    });

    it('logs request completion event', function () {
        $mockLogger = m::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')
            ->once()
            ->with(
                'GitHub API request completed',
                m::contains([
                    'event' => LogEvent::REQUEST_COMPLETE->value,
                    'request_class' => \JordanPartridge\GithubClient\Requests\Repos\Get::class,
                    'status_code' => 200,
                ])
            );

        $connector = new GithubConnector('test-token', new GithubLogger($mockLogger));

        $mockClient = new MockClient([
            MockResponse::make([
                'id' => 1,
                'node_id' => 'MDEwOlJlcG9zaXRvcnkxMjk2MjY5',
                'name' => 'test-repo',
                'full_name' => 'owner/test-repo',
                'private' => false,
                'owner' => [
                    'login' => 'owner',
                    'id' => 1,
                ],
                'html_url' => 'https://github.com/owner/test-repo',
                'description' => 'Test repo',
                'fork' => false,
                'url' => 'https://api.github.com/repos/owner/test-repo',
                'forks_url' => 'https://api.github.com/repos/owner/test-repo/forks',
                'keys_url' => 'https://api.github.com/repos/owner/test-repo/keys{/key_id}',
                'collaborators_url' => 'https://api.github.com/repos/owner/test-repo/collaborators{/collaborator}',
                'teams_url' => 'https://api.github.com/repos/owner/test-repo/teams',
                'hooks_url' => 'https://api.github.com/repos/owner/test-repo/hooks',
                'issue_events_url' => 'https://api.github.com/repos/owner/test-repo/issues/events{/number}',
                'events_url' => 'https://api.github.com/repos/owner/test-repo/events',
                'assignees_url' => 'https://api.github.com/repos/owner/test-repo/assignees{/user}',
                'branches_url' => 'https://api.github.com/repos/owner/test-repo/branches{/branch}',
                'tags_url' => 'https://api.github.com/repos/owner/test-repo/tags',
                'blobs_url' => 'https://api.github.com/repos/owner/test-repo/git/blobs{/sha}',
                'git_tags_url' => 'https://api.github.com/repos/owner/test-repo/git/tags{/sha}',
                'git_refs_url' => 'https://api.github.com/repos/owner/test-repo/git/refs{/sha}',
                'trees_url' => 'https://api.github.com/repos/owner/test-repo/git/trees{/sha}',
                'statuses_url' => 'https://api.github.com/repos/owner/test-repo/statuses/{sha}',
                'languages_url' => 'https://api.github.com/repos/owner/test-repo/languages',
                'stargazers_url' => 'https://api.github.com/repos/owner/test-repo/stargazers',
                'contributors_url' => 'https://api.github.com/repos/owner/test-repo/contributors',
                'subscribers_url' => 'https://api.github.com/repos/owner/test-repo/subscribers',
                'subscription_url' => 'https://api.github.com/repos/owner/test-repo/subscription',
                'commits_url' => 'https://api.github.com/repos/owner/test-repo/commits{/sha}',
                'git_commits_url' => 'https://api.github.com/repos/owner/test-repo/git/commits{/sha}',
                'comments_url' => 'https://api.github.com/repos/owner/test-repo/comments{/number}',
                'issue_comment_url' => 'https://api.github.com/repos/owner/test-repo/issues/comments{/number}',
                'contents_url' => 'https://api.github.com/repos/owner/test-repo/contents/{+path}',
                'compare_url' => 'https://api.github.com/repos/owner/test-repo/compare/{base}...{head}',
                'merges_url' => 'https://api.github.com/repos/owner/test-repo/merges',
                'archive_url' => 'https://api.github.com/repos/owner/test-repo/{archive_format}{/ref}',
                'downloads_url' => 'https://api.github.com/repos/owner/test-repo/downloads',
                'issues_url' => 'https://api.github.com/repos/owner/test-repo/issues{/number}',
                'pulls_url' => 'https://api.github.com/repos/owner/test-repo/pulls{/number}',
                'releases_url' => 'https://api.github.com/repos/owner/test-repo/releases{/id}',
                'deployments_url' => 'https://api.github.com/repos/owner/test-repo/deployments',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-01-01T00:00:00Z',
                'git_url' => 'git://github.com/owner/test-repo.git',
                'ssh_url' => 'git@github.com:owner/test-repo.git',
                'clone_url' => 'https://github.com/owner/test-repo.git',
                'svn_url' => 'https://github.com/owner/test-repo',
                'homepage' => null,
                'size' => 108,
                'stargazers_count' => 0,
                'watchers_count' => 0,
                'language' => null,
                'has_issues' => true,
                'has_projects' => true,
                'has_wiki' => true,
                'has_pages' => false,
                'has_downloads' => true,
                'archived' => false,
                'disabled' => false,
                'open_issues_count' => 0,
                'license' => null,
                'allow_forking' => true,
                'is_template' => false,
                'web_commit_signoff_required' => false,
                'topics' => [],
                'visibility' => 'public',
                'forks' => 0,
                'open_issues' => 0,
                'watchers' => 0,
                'default_branch' => 'main',
                'score' => 1.0,
            ], 200),
        ]);

        $connector->send(new \JordanPartridge\GithubClient\Requests\Repos\Get(\JordanPartridge\GithubClient\ValueObjects\Repo::fromFullName('owner/test-repo')), $mockClient);
    });

    it('logs request error event on failure', function () {
        $mockLogger = m::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('error')
            ->once()
            ->with(
                'GitHub API request failed',
                m::contains([
                    'event' => LogEvent::REQUEST_ERROR->value,
                    'request_class' => \JordanPartridge\GithubClient\Requests\Repos\Get::class,
                    'exception_class' => \JordanPartridge\GithubClient\Exceptions\ResourceNotFoundException::class,
                ])
            );

        $connector = new GithubConnector('test-token', new GithubLogger($mockLogger));

        $mockClient = new MockClient([
            MockResponse::make(['message' => 'Not Found'], 404),
        ]);

        try {
            $connector->send(new \JordanPartridge\GithubClient\Requests\Repos\Get(\JordanPartridge\GithubClient\ValueObjects\Repo::fromFullName('owner/nonexistent')), $mockClient);
        } catch (\JordanPartridge\GithubClient\Exceptions\ResourceNotFoundException $e) {
            // Expected
        }
    });

    it('does not log request/response bodies', function () {
        $mockLogger = m::mock(LoggerInterface::class);
        // Expect only info calls, not any that would contain body data
        $mockLogger->shouldReceive('info')
            ->times(2)
            ->andReturn();

        $connector = new GithubConnector('test-token', new GithubLogger($mockLogger));

        $mockClient = new MockClient([
            MockResponse::make([
                'id' => 1,
                'node_id' => 'MDEwOlJlcG9zaXRvcnkxMjk2MjY5',
                'name' => 'test-repo',
                'full_name' => 'owner/test-repo',
                'private' => false,
                'owner' => [
                    'login' => 'owner',
                    'id' => 1,
                ],
                'html_url' => 'https://github.com/owner/test-repo',
                'description' => 'Test repo with lots of data',
                'fork' => false,
                'url' => 'https://api.github.com/repos/owner/test-repo',
                'forks_url' => 'https://api.github.com/repos/owner/test-repo/forks',
                'keys_url' => 'https://api.github.com/repos/owner/test-repo/keys{/key_id}',
                'collaborators_url' => 'https://api.github.com/repos/owner/test-repo/collaborators{/collaborator}',
                'teams_url' => 'https://api.github.com/repos/owner/test-repo/teams',
                'hooks_url' => 'https://api.github.com/repos/owner/test-repo/hooks',
                'issue_events_url' => 'https://api.github.com/repos/owner/test-repo/issues/events{/number}',
                'events_url' => 'https://api.github.com/repos/owner/test-repo/events',
                'assignees_url' => 'https://api.github.com/repos/owner/test-repo/assignees{/user}',
                'branches_url' => 'https://api.github.com/repos/owner/test-repo/branches{/branch}',
                'tags_url' => 'https://api.github.com/repos/owner/test-repo/tags',
                'blobs_url' => 'https://api.github.com/repos/owner/test-repo/git/blobs{/sha}',
                'git_tags_url' => 'https://api.github.com/repos/owner/test-repo/git/tags{/sha}',
                'git_refs_url' => 'https://api.github.com/repos/owner/test-repo/git/refs{/sha}',
                'trees_url' => 'https://api.github.com/repos/owner/test-repo/git/trees{/sha}',
                'statuses_url' => 'https://api.github.com/repos/owner/test-repo/statuses/{sha}',
                'languages_url' => 'https://api.github.com/repos/owner/test-repo/languages',
                'stargazers_url' => 'https://api.github.com/repos/owner/test-repo/stargazers',
                'contributors_url' => 'https://api.github.com/repos/owner/test-repo/contributors',
                'subscribers_url' => 'https://api.github.com/repos/owner/test-repo/subscribers',
                'subscription_url' => 'https://api.github.com/repos/owner/test-repo/subscription',
                'commits_url' => 'https://api.github.com/repos/owner/test-repo/commits{/sha}',
                'git_commits_url' => 'https://api.github.com/repos/owner/test-repo/git/commits{/sha}',
                'comments_url' => 'https://api.github.com/repos/owner/test-repo/comments{/number}',
                'issue_comment_url' => 'https://api.github.com/repos/owner/test-repo/issues/comments{/number}',
                'contents_url' => 'https://api.github.com/repos/owner/test-repo/contents/{+path}',
                'compare_url' => 'https://api.github.com/repos/owner/test-repo/compare/{base}...{head}',
                'merges_url' => 'https://api.github.com/repos/owner/test-repo/merges',
                'archive_url' => 'https://api.github.com/repos/owner/test-repo/{archive_format}{/ref}',
                'downloads_url' => 'https://api.github.com/repos/owner/test-repo/downloads',
                'issues_url' => 'https://api.github.com/repos/owner/test-repo/issues{/number}',
                'pulls_url' => 'https://api.github.com/repos/owner/test-repo/pulls{/number}',
                'releases_url' => 'https://api.github.com/repos/owner/test-repo/releases{/id}',
                'deployments_url' => 'https://api.github.com/repos/owner/test-repo/deployments',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-01-01T00:00:00Z',
                'git_url' => 'git://github.com/owner/test-repo.git',
                'ssh_url' => 'git@github.com:owner/test-repo.git',
                'clone_url' => 'https://github.com/owner/test-repo.git',
                'svn_url' => 'https://github.com/owner/test-repo',
                'homepage' => null,
                'size' => 108,
                'stargazers_count' => 0,
                'watchers_count' => 0,
                'language' => null,
                'has_issues' => true,
                'has_projects' => true,
                'has_wiki' => true,
                'has_pages' => false,
                'has_downloads' => true,
                'archived' => false,
                'disabled' => false,
                'open_issues_count' => 0,
                'license' => null,
                'allow_forking' => true,
                'is_template' => false,
                'web_commit_signoff_required' => false,
                'topics' => [],
                'visibility' => 'public',
                'forks' => 0,
                'open_issues' => 0,
                'watchers' => 0,
                'default_branch' => 'main',
                'score' => 1.0,
            ], 200),
        ]);

        $connector->send(new \JordanPartridge\GithubClient\Requests\Repos\Get(\JordanPartridge\GithubClient\ValueObjects\Repo::fromFullName('owner/test-repo')), $mockClient);

        // Verify no requests to info that would contain body content
        $mockLogger->shouldHaveReceived('info')->times(2);
    });

    it('logs authentication refresh event', function () {
        $mockLogger = m::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')
            ->times(3)
            ->andReturn();

        $connector = new GithubConnector('test-token', new GithubLogger($mockLogger));

        $mockClient = new MockClient([
            MockResponse::make([
                'id' => 1,
                'node_id' => 'MDEwOlJlcG9zaXRvcnkxMjk2MjY5',
                'name' => 'test-repo',
                'full_name' => 'owner/test-repo',
                'private' => false,
                'owner' => [
                    'login' => 'owner',
                    'id' => 1,
                ],
                'html_url' => 'https://github.com/owner/test-repo',
                'description' => 'Test repo',
                'fork' => false,
                'url' => 'https://api.github.com/repos/owner/test-repo',
                'forks_url' => 'https://api.github.com/repos/owner/test-repo/forks',
                'keys_url' => 'https://api.github.com/repos/owner/test-repo/keys{/key_id}',
                'collaborators_url' => 'https://api.github.com/repos/owner/test-repo/collaborators{/collaborator}',
                'teams_url' => 'https://api.github.com/repos/owner/test-repo/teams',
                'hooks_url' => 'https://api.github.com/repos/owner/test-repo/hooks',
                'issue_events_url' => 'https://api.github.com/repos/owner/test-repo/issues/events{/number}',
                'events_url' => 'https://api.github.com/repos/owner/test-repo/events',
                'assignees_url' => 'https://api.github.com/repos/owner/test-repo/assignees{/user}',
                'branches_url' => 'https://api.github.com/repos/owner/test-repo/branches{/branch}',
                'tags_url' => 'https://api.github.com/repos/owner/test-repo/tags',
                'blobs_url' => 'https://api.github.com/repos/owner/test-repo/git/blobs{/sha}',
                'git_tags_url' => 'https://api.github.com/repos/owner/test-repo/git/tags{/sha}',
                'git_refs_url' => 'https://api.github.com/repos/owner/test-repo/git/refs{/sha}',
                'trees_url' => 'https://api.github.com/repos/owner/test-repo/git/trees{/sha}',
                'statuses_url' => 'https://api.github.com/repos/owner/test-repo/statuses/{sha}',
                'languages_url' => 'https://api.github.com/repos/owner/test-repo/languages',
                'stargazers_url' => 'https://api.github.com/repos/owner/test-repo/stargazers',
                'contributors_url' => 'https://api.github.com/repos/owner/test-repo/contributors',
                'subscribers_url' => 'https://api.github.com/repos/owner/test-repo/subscribers',
                'subscription_url' => 'https://api.github.com/repos/owner/test-repo/subscription',
                'commits_url' => 'https://api.github.com/repos/owner/test-repo/commits{/sha}',
                'git_commits_url' => 'https://api.github.com/repos/owner/test-repo/git/commits{/sha}',
                'comments_url' => 'https://api.github.com/repos/owner/test-repo/comments{/number}',
                'issue_comment_url' => 'https://api.github.com/repos/owner/test-repo/issues/comments{/number}',
                'contents_url' => 'https://api.github.com/repos/owner/test-repo/contents/{+path}',
                'compare_url' => 'https://api.github.com/repos/owner/test-repo/compare/{base}...{head}',
                'merges_url' => 'https://api.github.com/repos/owner/test-repo/merges',
                'archive_url' => 'https://api.github.com/repos/owner/test-repo/{archive_format}{/ref}',
                'downloads_url' => 'https://api.github.com/repos/owner/test-repo/downloads',
                'issues_url' => 'https://api.github.com/repos/owner/test-repo/issues{/number}',
                'pulls_url' => 'https://api.github.com/repos/owner/test-repo/pulls{/number}',
                'releases_url' => 'https://api.github.com/repos/owner/test-repo/releases{/id}',
                'deployments_url' => 'https://api.github.com/repos/owner/test-repo/deployments',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-01-01T00:00:00Z',
                'git_url' => 'git://github.com/owner/test-repo.git',
                'ssh_url' => 'git@github.com:owner/test-repo.git',
                'clone_url' => 'https://github.com/owner/test-repo.git',
                'svn_url' => 'https://github.com/owner/test-repo',
                'homepage' => null,
                'size' => 108,
                'stargazers_count' => 0,
                'watchers_count' => 0,
                'language' => null,
                'has_issues' => true,
                'has_projects' => true,
                'has_wiki' => true,
                'has_pages' => false,
                'has_downloads' => true,
                'archived' => false,
                'disabled' => false,
                'open_issues_count' => 0,
                'license' => null,
                'allow_forking' => true,
                'is_template' => false,
                'web_commit_signoff_required' => false,
                'topics' => [],
                'visibility' => 'public',
                'forks' => 0,
                'open_issues' => 0,
                'watchers' => 0,
                'default_branch' => 'main',
                'score' => 1.0,
            ], 200),
        ]);

        $connector->send(new \JordanPartridge\GithubClient\Requests\Repos\Get(\JordanPartridge\GithubClient\ValueObjects\Repo::fromFullName('owner/test-repo')), $mockClient);

        $mockLogger->shouldHaveReceived('info')->times(2);
    });

    it('uses a null logger by default when not provided', function () {
        // Should not throw any errors
        $connector = new GithubConnector('test-token');

        $mockClient = new MockClient([
            MockResponse::make([
                'id' => 1,
                'node_id' => 'MDEwOlJlcG9zaXRvcnkxMjk2MjY5',
                'name' => 'test-repo',
                'full_name' => 'owner/test-repo',
                'private' => false,
                'owner' => [
                    'login' => 'owner',
                    'id' => 1,
                ],
                'html_url' => 'https://github.com/owner/test-repo',
                'description' => 'Test repo',
                'fork' => false,
                'url' => 'https://api.github.com/repos/owner/test-repo',
                'forks_url' => 'https://api.github.com/repos/owner/test-repo/forks',
                'keys_url' => 'https://api.github.com/repos/owner/test-repo/keys{/key_id}',
                'collaborators_url' => 'https://api.github.com/repos/owner/test-repo/collaborators{/collaborator}',
                'teams_url' => 'https://api.github.com/repos/owner/test-repo/teams',
                'hooks_url' => 'https://api.github.com/repos/owner/test-repo/hooks',
                'issue_events_url' => 'https://api.github.com/repos/owner/test-repo/issues/events{/number}',
                'events_url' => 'https://api.github.com/repos/owner/test-repo/events',
                'assignees_url' => 'https://api.github.com/repos/owner/test-repo/assignees{/user}',
                'branches_url' => 'https://api.github.com/repos/owner/test-repo/branches{/branch}',
                'tags_url' => 'https://api.github.com/repos/owner/test-repo/tags',
                'blobs_url' => 'https://api.github.com/repos/owner/test-repo/git/blobs{/sha}',
                'git_tags_url' => 'https://api.github.com/repos/owner/test-repo/git/tags{/sha}',
                'git_refs_url' => 'https://api.github.com/repos/owner/test-repo/git/refs{/sha}',
                'trees_url' => 'https://api.github.com/repos/owner/test-repo/git/trees{/sha}',
                'statuses_url' => 'https://api.github.com/repos/owner/test-repo/statuses/{sha}',
                'languages_url' => 'https://api.github.com/repos/owner/test-repo/languages',
                'stargazers_url' => 'https://api.github.com/repos/owner/test-repo/stargazers',
                'contributors_url' => 'https://api.github.com/repos/owner/test-repo/contributors',
                'subscribers_url' => 'https://api.github.com/repos/owner/test-repo/subscribers',
                'subscription_url' => 'https://api.github.com/repos/owner/test-repo/subscription',
                'commits_url' => 'https://api.github.com/repos/owner/test-repo/commits{/sha}',
                'git_commits_url' => 'https://api.github.com/repos/owner/test-repo/git/commits{/sha}',
                'comments_url' => 'https://api.github.com/repos/owner/test-repo/comments{/number}',
                'issue_comment_url' => 'https://api.github.com/repos/owner/test-repo/issues/comments{/number}',
                'contents_url' => 'https://api.github.com/repos/owner/test-repo/contents/{+path}',
                'compare_url' => 'https://api.github.com/repos/owner/test-repo/compare/{base}...{head}',
                'merges_url' => 'https://api.github.com/repos/owner/test-repo/merges',
                'archive_url' => 'https://api.github.com/repos/owner/test-repo/{archive_format}{/ref}',
                'downloads_url' => 'https://api.github.com/repos/owner/test-repo/downloads',
                'issues_url' => 'https://api.github.com/repos/owner/test-repo/issues{/number}',
                'pulls_url' => 'https://api.github.com/repos/owner/test-repo/pulls{/number}',
                'releases_url' => 'https://api.github.com/repos/owner/test-repo/releases{/id}',
                'deployments_url' => 'https://api.github.com/repos/owner/test-repo/deployments',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'pushed_at' => '2024-01-01T00:00:00Z',
                'git_url' => 'git://github.com/owner/test-repo.git',
                'ssh_url' => 'git@github.com:owner/test-repo.git',
                'clone_url' => 'https://github.com/owner/test-repo.git',
                'svn_url' => 'https://github.com/owner/test-repo',
                'homepage' => null,
                'size' => 108,
                'stargazers_count' => 0,
                'watchers_count' => 0,
                'language' => null,
                'has_issues' => true,
                'has_projects' => true,
                'has_wiki' => true,
                'has_pages' => false,
                'has_downloads' => true,
                'archived' => false,
                'disabled' => false,
                'open_issues_count' => 0,
                'license' => null,
                'allow_forking' => true,
                'is_template' => false,
                'web_commit_signoff_required' => false,
                'topics' => [],
                'visibility' => 'public',
                'forks' => 0,
                'open_issues' => 0,
                'watchers' => 0,
                'default_branch' => 'main',
                'score' => 1.0,
            ], 200),
        ]);

        $response = $connector->send(new \JordanPartridge\GithubClient\Requests\Repos\Get(\JordanPartridge\GithubClient\ValueObjects\Repo::fromFullName('owner/test-repo')), $mockClient);

        expect($response)->toBeSuccessful();
    });
});
