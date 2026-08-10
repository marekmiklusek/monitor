<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

final readonly class RegenerateProjectToken
{
    public function execute(Project $project): string
    {
        $token = Project::generateToken();

        $project->forceFill(['token_hash' => $token['hash']])->save();

        return $token['plain'];
    }
}
