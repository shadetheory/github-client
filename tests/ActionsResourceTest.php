<?php

use JordanPartridge\GithubClient\Facades\Github;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config(['github-client.token' => 'fake-token']);
});

describe('ActionsResource', function () {
    describe('listWorkflows', function () {
        it('can list workflows for a repository', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows*' => MockResponse::make([
                    'total_count' => 2,
                    'workflows' => [
                        [
                            'id' => 161335,
                            'name' => 'CI',
                            'path' => '.github/workflows/blank.yml',
                            'state' => 'active',
                            'created_at' => '2020-01-08T23:48:37.000Z',
                            'updated_at' => '2020-01-08T23:50:21.000Z',
                        ],
                        [
                            'id' => 269289,
                            'name' => 'CodeQL',
                            'path' => '.github/workflows/codeql-analysis.yml',
                            'state' => 'active',
                            'created_at' => '2020-11-02T22:22:58.000Z',
                            'updated_at' => '2020-11-02T22:22:58.000Z',
                        ],
                    ],
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->listWorkflows('owner', 'repo');

            $data = $response->json();
            expect($data)
                ->toBeArray()
                ->toHaveKey('workflows')
                ->and($data['workflows'])->toHaveCount(2)
                ->and($data['workflows'][0])->toHaveKeys(['id', 'name', 'path', 'state'])
                ->and($data['workflows'][0]['name'])->toBe('CI')
                ->and($response->status())->toBe(200);
        });

        it('validates per_page parameter', function () {
            expect(fn () => Github::actions()->listWorkflows(
                'owner',
                'repo',
                per_page: 101,
            ))->toThrow(InvalidArgumentException::class, 'Per page must be between 1 and 100');
        });

        it('accepts valid pagination parameters', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflows' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->listWorkflows(
                'owner',
                'repo',
                per_page: 50,
                page: 2,
            );

            expect($response->status())->toBe(200);
        });
    });

    describe('getWorkflowRuns', function () {
        it('can get workflow runs for a workflow', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335/runs*' => MockResponse::make([
                    'total_count' => 1,
                    'workflow_runs' => [
                        [
                            'id' => 30433642,
                            'name' => 'Build',
                            'head_branch' => 'main',
                            'head_sha' => '009b8a3a9ccbb128af87f9b1c0732c71an329da9',
                            'status' => 'completed',
                            'conclusion' => 'success',
                            'workflow_id' => 161335,
                            'created_at' => '2020-01-22T19:33:08Z',
                            'updated_at' => '2020-01-22T19:33:08Z',
                        ],
                    ],
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->getWorkflowRuns('owner', 'repo', 161335);

            $data = $response->json();
            expect($data)
                ->toBeArray()
                ->toHaveKey('workflow_runs')
                ->and($data['workflow_runs'])->toHaveCount(1)
                ->and($data['workflow_runs'][0])->toHaveKeys(['id', 'name', 'status', 'conclusion'])
                ->and($data['workflow_runs'][0]['status'])->toBe('completed')
                ->and($response->status())->toBe(200);
        });

        it('validates per_page parameter', function () {
            expect(fn () => Github::actions()->getWorkflowRuns(
                'owner',
                'repo',
                161335,
                per_page: 0,
            ))->toThrow(InvalidArgumentException::class, 'Per page must be between 1 and 100');
        });

        it('validates status parameter', function () {
            expect(fn () => Github::actions()->getWorkflowRuns(
                'owner',
                'repo',
                161335,
                status: 'invalid_status',
            ))->toThrow(InvalidArgumentException::class, 'Invalid status provided');
        });

        it('validates conclusion parameter', function () {
            expect(fn () => Github::actions()->getWorkflowRuns(
                'owner',
                'repo',
                161335,
                conclusion: 'invalid_conclusion',
            ))->toThrow(InvalidArgumentException::class, 'Invalid conclusion provided');
        });

        it('accepts valid filter parameters', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflow_runs' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->getWorkflowRuns(
                'owner',
                'repo',
                161335,
                per_page: 20,
                page: 1,
                status: 'completed',
                conclusion: 'success',
                branch: 'main',
            );

            expect($response->status())->toBe(200);
        });
    });

    describe('triggerWorkflow', function () {
        it('can trigger a workflow dispatch', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335/dispatches' => MockResponse::make([], 204),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->triggerWorkflow(
                'owner',
                'repo',
                161335,
                [
                    'ref' => 'main',
                    'inputs' => [
                        'environment' => 'production',
                        'debug' => 'false',
                    ],
                ],
            );

            expect($response->status())->toBe(204);
        });

        it('requires ref parameter', function () {
            expect(fn () => Github::actions()->triggerWorkflow(
                'owner',
                'repo',
                161335,
                [],
            ))->toThrow(InvalidArgumentException::class, 'The "ref" field is required for workflow dispatch');
        });

        it('validates inputs parameter is array', function () {
            expect(fn () => Github::actions()->triggerWorkflow(
                'owner',
                'repo',
                161335,
                [
                    'ref' => 'main',
                    'inputs' => 'invalid',
                ],
            ))->toThrow(InvalidArgumentException::class, 'The "inputs" field must be an array');
        });

        it('accepts workflow dispatch without inputs', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make([], 204),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->triggerWorkflow(
                'owner',
                'repo',
                161335,
                ['ref' => 'main'],
            );

            expect($response->status())->toBe(204);
        });
    });

    describe('integration with main Github class', function () {
        it('can access actions resource through Github facade', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflows' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $actionsResource = Github::actions();
            $response = $actionsResource->listWorkflows('owner', 'repo');

            expect($response->status())->toBe(200);
        });
    });

    describe('getWorkflow', function () {
        it('can get a specific workflow by ID', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335' => MockResponse::make([
                    'id' => 161335,
                    'name' => 'CI',
                    'path' => '.github/workflows/blank.yml',
                    'state' => 'active',
                    'created_at' => '2020-01-08T23:48:37.000Z',
                    'updated_at' => '2020-01-08T23:50:21.000Z',
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->getWorkflow('owner', 'repo', 161335);

            $data = $response->json();
            expect($data)
                ->toBeArray()
                ->and($data['name'])->toBe('CI')
                ->and($response->status())->toBe(200);
        });
    });

    describe('getLatestWorkflowRuns', function () {
        it('can get the latest workflow runs', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335/runs*' => MockResponse::make([
                    'total_count' => 2,
                    'workflow_runs' => [
                        [
                            'id' => 30433642,
                            'name' => 'Build',
                            'status' => 'completed',
                            'conclusion' => 'success',
                            'created_at' => '2020-01-22T19:33:08Z',
                            'updated_at' => '2020-01-22T19:35:08Z',
                        ],
                        [
                            'id' => 30433641,
                            'name' => 'Build',
                            'status' => 'completed',
                            'conclusion' => 'failure',
                            'created_at' => '2020-01-21T19:33:08Z',
                            'updated_at' => '2020-01-21T19:35:08Z',
                        ],
                    ],
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $runs = Github::actions()->getLatestWorkflowRuns('owner', 'repo', 161335, limit: 5);

            expect($runs)
                ->toBeArray()
                ->toHaveCount(2)
                ->and($runs[0]['conclusion'])->toBe('success')
                ->and($runs[1]['conclusion'])->toBe('failure');
        });

        it('applies status filter', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflow_runs' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $response = Github::actions()->getLatestWorkflowRuns(
                'owner',
                'repo',
                161335,
                limit: 10,
                status: 'completed',
            );

            expect($response)->toBeArray();
        });
    });

    describe('analyzeSuccessRate', function () {
        it('correctly calculates success rate', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335/runs*' => MockResponse::make([
                    'total_count' => 5,
                    'workflow_runs' => [
                        ['conclusion' => 'success', 'created_at' => '2020-01-22T19:33:08Z', 'updated_at' => '2020-01-22T19:35:08Z'],
                        ['conclusion' => 'success', 'created_at' => '2020-01-22T19:33:08Z', 'updated_at' => '2020-01-22T19:35:08Z'],
                        ['conclusion' => 'success', 'created_at' => '2020-01-22T19:33:08Z', 'updated_at' => '2020-01-22T19:35:08Z'],
                        ['conclusion' => 'failure', 'created_at' => '2020-01-22T19:33:08Z', 'updated_at' => '2020-01-22T19:35:08Z'],
                        ['conclusion' => 'skipped', 'created_at' => '2020-01-22T19:33:08Z', 'updated_at' => '2020-01-22T19:35:08Z'],
                    ],
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $analysis = Github::actions()->analyzeSuccessRate('owner', 'repo', 161335, limit: 100);

            expect($analysis)
                ->toBeArray()
                ->and($analysis['total'])->toBe(5)
                ->and($analysis['success'])->toBe(3)
                ->and($analysis['failure'])->toBe(1)
                ->and($analysis['skipped'])->toBe(1)
                ->and($analysis['success_rate'])->toBe(0.6);
        });

        it('returns zero when no runs exist', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflow_runs' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $analysis = Github::actions()->analyzeSuccessRate('owner', 'repo', 161335, limit: 100);

            expect($analysis['total'])->toBe(0)
                ->and($analysis['success_rate'])->toBeNull();
        });
    });

    describe('analyzeDuration', function () {
        it('calculates average duration', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335/runs*' => MockResponse::make([
                    'total_count' => 3,
                    'workflow_runs' => [
                        ['created_at' => '2020-01-22T19:33:08Z', 'updated_at' => '2020-01-22T19:35:08Z'],
                        ['created_at' => '2020-01-22T20:33:08Z', 'updated_at' => '2020-01-22T20:38:08Z'],
                        ['created_at' => '2020-01-22T21:33:08Z', 'updated_at' => '2020-01-22T21:34:08Z'],
                    ],
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $analysis = Github::actions()->analyzeDuration('owner', 'repo', 161335, limit: 100);

            expect($analysis)
                ->toBeArray()
                ->and($analysis['total'])->toBe(3)
                ->and($analysis['avg_duration_seconds'])->toBe(160)
                ->and($analysis['min_duration_seconds'])->toBe(60)
                ->and($analysis['max_duration_seconds'])->toBe(300);
        });

        it('handles empty runs', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflow_runs' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $analysis = Github::actions()->analyzeDuration('owner', 'repo', 161335, limit: 100);

            expect($analysis['total'])->toBe(0)
                ->and($analysis['avg_duration_seconds'])->toBeNull();
        });
    });

    describe('getLatestRun', function () {
        it('returns the latest run info when runs exist', function () {
            $mockClient = new MockClient([
                'repos/owner/repo/actions/workflows/161335/runs*' => MockResponse::make([
                    'total_count' => 1,
                    'workflow_runs' => [
                        [
                            'status' => 'completed',
                            'conclusion' => 'success',
                            'run_number' => 42,
                            'created_at' => '2020-01-22T19:33:08Z',
                            'updated_at' => '2020-01-22T19:35:08Z',
                        ],
                    ],
                ], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $latestRun = Github::actions()->getLatestRun('owner', 'repo', 161335);

            expect($latestRun)
                ->toBeArray()
                ->and($latestRun['status'])->toBe('completed')
                ->and($latestRun['conclusion'])->toBe('success')
                ->and($latestRun['run_number'])->toBe(42);
        });

        it('returns null values when no runs exist', function () {
            $mockClient = new MockClient([
                '*' => MockResponse::make(['workflow_runs' => []], 200),
            ]);

            Github::connector()->withMockClient($mockClient);

            $latestRun = Github::actions()->getLatestRun('owner', 'repo', 161335);

            expect($latestRun)
                ->toBeArray()
                ->and($latestRun['status'])->toBeNull()
                ->and($latestRun['conclusion'])->toBeNull()
                ->and($latestRun['run_number'])->toBeNull();
        });
    });
});
