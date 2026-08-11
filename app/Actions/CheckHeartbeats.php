<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use App\Notifications\AdminNotifiable;
use App\Notifications\HeartbeatMissing;
use App\Notifications\HeartbeatRecovered;

final readonly class CheckHeartbeats
{
    public function execute(): void
    {
        $threshold = Project::heartbeatThreshold();

        Project::query()->eachById(function (Project $project) use ($threshold): void {
            $isStale = $project->last_heartbeat_at === null
                || $project->last_heartbeat_at->lt($threshold);

            if ($isStale) {
                $this->alert($project);

                return;
            }

            $this->recover($project);
        });
    }

    private function alert(Project $project): void
    {
        if ($project->heartbeat_alerted_at !== null) {
            return;
        }

        $project->fill(['heartbeat_alerted_at' => now()])->save();

        $this->notify(new HeartbeatMissing($project));
    }

    private function recover(Project $project): void
    {
        if ($project->heartbeat_alerted_at === null) {
            return;
        }

        $project->fill(['heartbeat_alerted_at' => null])->save();

        $this->notify(new HeartbeatRecovered($project));
    }

    private function notify(HeartbeatMissing|HeartbeatRecovered $notification): void
    {
        (new AdminNotifiable)->notify($notification);
    }
}
