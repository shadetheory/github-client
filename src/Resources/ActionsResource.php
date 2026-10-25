<?php

namespace JordanPartridge\GithubClient\Resources;

use JordanPartridge\GithubClient\Requests\Actions\GetWorkflow;
use JordanPartridge\GithubClient\Requests\Actions\GetWorkflowRuns;
use JordanPartridge\GithubClient\Requests\Actions\ListWorkflows;
use JordanPartridge\GithubClient\Requests\Actions\TriggerWorkflow;
use Saloon\Http\Response;

/**
 * GitHub Actions Resource Handler
 *
 * This class provides methods to interact with GitHub's Actions endpoints.
 * It handles operations such as listing workflows, getting workflow runs,
 * triggering workflows, and analyzing workflow performance.
 *
 * @link https://docs.github.com/en/rest/actions/workflows Documentation for GitHub Actions API
 *
 * Usage example:
 * ```php
 * $workflows = $actions->listWorkflows('owner', 'repo');
 *
 * $runs = $actions->getWorkflowRuns('owner', 'repo', $workflowId);
 *
 * $result = $actions->triggerWorkflow('owner', 'repo', $workflowId, [
 *     'ref' => 'main',
 *     'inputs' => ['test' => 'value'],
 * ]);
 *
 * // Analysis methods
 * $latestRun = $actions->getLatestRun('owner', 'repo', $workflowId);
 * $successRate = $actions->analyzeSuccessRate('owner', 'repo', $workflowId);
 * $durationStats = $actions->analyzeDuration('owner', 'repo', $workflowId);
 * ```
 */
readonly class ActionsResource extends BaseResource
{
    /**
     * List workflows for a repository
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int|null  $per_page  Number of results per page (max 100)
     * @param  int|null  $page  Page number of the results to fetch
     *
     * @return Response Returns a Saloon response containing workflow data
     *
     * @link https://docs.github.com/en/rest/actions/workflows#list-repository-workflows
     */
    public function listWorkflows(
        string $owner,
        string $repo,
        ?int $per_page = null,
        ?int $page = null,
    ): Response {
        return $this->github()->connector()->send(new ListWorkflows(
            owner: $owner,
            repo: $repo,
            per_page: $per_page,
            page: $page,
        ));
    }

    /**
     * Get workflow runs for a workflow
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     * @param  int|null  $per_page  Number of results per page (max 100)
     * @param  int|null  $page  Page number of the results to fetch
     * @param  string|null  $status  Filter by status (completed, action_required, cancelled, failure, neutral, skipped, stale, success, timed_out, in_progress, queued, requested, waiting)
     * @param  string|null  $conclusion  Filter by conclusion (action_required, cancelled, failure, neutral, success, skipped, stale, timed_out)
     * @param  string|null  $branch  Filter by branch name
     *
     * @return Response Returns a Saloon response containing workflow run data
     *
     * @link https://docs.github.com/en/rest/actions/workflow-runs#list-workflow-runs-for-a-workflow
     */
    public function getWorkflowRuns(
        string $owner,
        string $repo,
        int $workflow_id,
        ?int $per_page = null,
        ?int $page = null,
        ?string $status = null,
        ?string $conclusion = null,
        ?string $branch = null,
    ): Response {
        return $this->github()->connector()->send(new GetWorkflowRuns(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
            per_page: $per_page,
            page: $page,
            status: $status,
            conclusion: $conclusion,
            branch: $branch,
        ));
    }

    /**
     * Trigger a workflow dispatch event
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     * @param  array  $data  The workflow dispatch data including 'ref' and optional 'inputs'
     *
     * @return Response Returns a Saloon response
     *
     * @link https://docs.github.com/en/rest/actions/workflows#create-a-workflow-dispatch-event
     */
    public function triggerWorkflow(
        string $owner,
        string $repo,
        int $workflow_id,
        array $data = [],
    ): Response {
        return $this->github()->connector()->send(new TriggerWorkflow(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
            data: $data,
        ));
    }

    /**
     * Get a specific workflow by ID
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     *
     * @return Response Returns a Saloon response containing workflow details
     *
     * @link https://docs.github.com/en/rest/actions/workflows#get-a-workflow
     */
    public function getWorkflow(
        string $owner,
        string $repo,
        int $workflow_id,
    ): Response {
        return $this->github()->connector()->send(new GetWorkflow(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
        ));
    }

    /**
     * Get the latest workflow runs for analysis
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     * @param  int  $limit  Number of runs to fetch (max: 100)
     * @param  string|null  $status  Filter by status
     * @param  string|null  $conclusion  Filter by conclusion
     *
     * @return array<array-key, mixed> Array of workflow runs for analysis
     */
    public function getLatestWorkflowRuns(
        string $owner,
        string $repo,
        int $workflow_id,
        int $limit = 10,
        ?string $status = null,
        ?string $conclusion = null,
    ): array {
        $response = $this->getWorkflowRuns(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
            per_page: $limit,
            status: $status,
            conclusion: $conclusion,
        );

        $data = $response->json();

        return $data['workflow_runs'] ?? [];
    }

    /**
     * Analyze workflow run success rate
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     * @param  int  $limit  Number of runs to analyze (max: 100)
     *
     * @return array{total: int, success: int, failure: int, skipped: int, neutral: int, success_rate: float|null} Success rate analysis
     */
    public function analyzeSuccessRate(
        string $owner,
        string $repo,
        int $workflow_id,
        int $limit = 100,
    ): array {
        $runs = $this->getLatestWorkflowRuns(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
            limit: $limit,
            conclusion: 'success',
        );

        // Count by conclusion
        $counts = [
            'success' => 0,
            'failure' => 0,
            'skipped' => 0,
            'neutral' => 0,
        ];

        foreach ($runs as $run) {
            $conclusion = $run['conclusion'] ?? null;
            if (isset($counts[$conclusion])) {
                $counts[$conclusion]++;
            } else {
                $counts['neutral']++; // Treat unknown as neutral
            }
        }

        $total = count($runs);
        $successRate = $total > 0 ? $counts['success'] / $total : null;

        return [
            'total' => $total,
            'success' => $counts['success'],
            'failure' => $counts['failure'],
            'skipped' => $counts['skipped'],
            'neutral' => $counts['neutral'],
            'success_rate' => $successRate,
        ];
    }

    /**
     * Get average duration of workflow runs
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     * @param  int  $limit  Number of runs to analyze (max: 100)
     *
     * @return array{total: int, avg_duration_seconds: float|null, min_duration_seconds: int|null, max_duration_seconds: int|null} Duration statistics
     */
    public function analyzeDuration(
        string $owner,
        string $repo,
        int $workflow_id,
        int $limit = 100,
    ): array {
        $runs = $this->getLatestWorkflowRuns(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
            limit: $limit,
        );

        $durations = [];
        foreach ($runs as $run) {
            $createdAt = $run['created_at'] ?? null;
            $updatedAt = $run['updated_at'] ?? null;

            if ($createdAt && $updatedAt) {
                $created = strtotime($createdAt);
                $updated = strtotime($updatedAt);

                if ($created && $updated) {
                    $durations[] = $updated - $created;
                }
            }
        }

        $total = count($runs);
        $avgDuration = ! empty($durations) ? array_sum($durations) / count($durations) : null;
        $minDuration = ! empty($durations) ? min($durations) : null;
        $maxDuration = ! empty($durations) ? max($durations) : null;

        return [
            'total' => $total,
            'avg_duration_seconds' => $avgDuration,
            'min_duration_seconds' => $minDuration,
            'max_duration_seconds' => $maxDuration,
        ];
    }

    /**
     * Get most recent workflow run status
     *
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     *
     * @return array{status: string|null, conclusion: string|null, run_number: int|null, created_at: string|null, updated_at: string|null} Latest run info
     */
    public function getLatestRun(
        string $owner,
        string $repo,
        int $workflow_id,
    ): array {
        $runs = $this->getLatestWorkflowRuns(
            owner: $owner,
            repo: $repo,
            workflow_id: $workflow_id,
            limit: 1,
        );

        if (empty($runs)) {
            return [
                'status' => null,
                'conclusion' => null,
                'run_number' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        $run = $runs[0];

        return [
            'status' => $run['status'] ?? null,
            'conclusion' => $run['conclusion'] ?? null,
            'run_number' => $run['run_number'] ?? null,
            'created_at' => $run['created_at'] ?? null,
            'updated_at' => $run['updated_at'] ?? null,
        ];
    }
}
