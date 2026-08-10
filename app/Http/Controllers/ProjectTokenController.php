<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use App\Actions\RegenerateProjectToken;

final readonly class ProjectTokenController
{
    public function __construct(private RegenerateProjectToken $regenerateProjectToken)
    {
        // ...
    }

    public function __invoke(Project $project): RedirectResponse
    {
        $plain = $this->regenerateProjectToken->execute($project);

        return to_route('projects.index')->with('token', $plain);
    }
}
