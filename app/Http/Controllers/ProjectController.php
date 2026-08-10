<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Actions\CreateProject;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ProjectStoreRequest;

final readonly class ProjectController
{
    public function __construct(private CreateProject $createProject)
    {
        // ...
    }

    public function index(Request $request): Response
    {
        $projects = Project::query()->orderBy('name')->get();

        $token = $request->session()->get('token');

        return Inertia::render('projects/index', [
            'projects' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'environment' => $project->environment,
                'heartbeat_status' => $project->heartbeatStatus()->value,
                'last_heartbeat_at' => $project->last_heartbeat_at?->toIso8601String(),
                'created_at' => $project->created_at->toIso8601String(),
            ])->all(),
            'revealedToken' => is_string($token) ? $token : null,
        ]);
    }

    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $result = $this->createProject->execute(
            $request->string('name')->toString(),
            $request->string('environment')->toString(),
        );

        return to_route('projects.index')->with('token', $result['token']);
    }
}
