<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use App\Notifications\AdminNotifiable;
use App\Notifications\HeartbeatMissing;
use App\Notifications\HeartbeatRecovered;

final readonly class CheckHeartbeats
{
    /**
     * @return array{alerted: int, recovered: int}
     */
    public function execute(): array
    {
        $threshold = Project::heartbeatThreshold();

        $alerted = 0;
        $recovered = 0;

        Project::query()->eachById(function (Project $project) use ($threshold, &$alerted, &$recovered): void {
            $isStale = $project->last_heartbeat_at === null
                || $project->last_heartbeat_at->lt($threshold);

            if ($isStale) {
                $alerted += $this->alert($project);

                return;
            }

            $recovered += $this->recover($project);
        });

        return [
            'alerted' => $alerted,
            'recovered' => $recovered,
        ];
    }

    private function alert(Project $project): int
    {
        if ($project->heartbeat_alerted_at !== null) {
            return 0;
        }

        $project->fill(['heartbeat_alerted_at' => now()])->save();

        $this->notify(new HeartbeatMissing($project));

        return 1;
    }

    private function recover(Project $project): int
    {
        if ($project->heartbeat_alerted_at === null) {
            return 0;
        }

        $project->fill(['heartbeat_alerted_at' => null])->save();

        $this->notify(new HeartbeatRecovered($project));

        return 1;
    }

    private function notify(HeartbeatMissing|HeartbeatRecovered $notification): void
    {
        (new AdminNotifiable)->notify($notification);
    }
}
