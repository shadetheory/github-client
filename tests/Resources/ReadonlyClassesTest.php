<?php

use JordanPartridge\GithubClient\Resources\{
    ActionsResource,
    CommentsResource,
    CommitResource,
    FileResource,
    InstallationsResource,
    IssuesResource,
    PullRequestResource,
    ReleasesResource,
    RepoResource,
};
use ReflectionClass;
use JordanPartridge\GithubClient\Resources\BaseResource;

test('all resource classes are readonly', function () {
    $resources = [
        ActionsResource::class,
        CommentsResource::class,
        CommitResource::class,
        FileResource::class,
        InstallationsResource::class,
        IssuesResource::class,
        PullRequestResource::class,
        ReleasesResource::class,
        RepoResource::class,
    ];

    foreach ($resources as $resource) {
        $reflection = new ReflectionClass($resource);
        expect($reflection->isReadOnly())->toBeTrue();
    }
});

test('BaseResource is readonly', function () {
    $reflection = new ReflectionClass(BaseResource::class);
    expect($reflection->isReadOnly())->toBeTrue();
});
