<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

final readonly class CreateProject
{
    /**
     * @return array{project: Project, token: string}
     */
    public function execute(string $name, string $environment): array
    {
        $token = Project::generateToken();

        $project = Project::query()->create([
            'name' => $name,
            'environment' => $environment,
            'token_hash' => $token['hash'],
            'last_heartbeat_at' => null,
            'heartbeat_alerted_at' => null,
        ]);

        return [
            'project' => $project,
            'token' => $token['plain'],
        ];
    }
}
