<?php

namespace JordanPartridge\GithubClient\Requests\Actions;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetWorkflow extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param  string  $owner  The account owner of the repository
     * @param  string  $repo  The name of the repository
     * @param  int  $workflow_id  The ID of the workflow
     */
    public function __construct(
        protected string $owner,
        protected string $repo,
        protected int $workflow_id,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}/actions/workflows/{$this->workflow_id}";
    }
}
