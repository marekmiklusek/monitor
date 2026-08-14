<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use App\Notifications\QueueStalled;
use App\Notifications\QueueRecovered;
use Illuminate\Support\Facades\Cache;
use App\Notifications\AdminNotifiable;
use App\Concerns\BuildsNotificationContent;
use Illuminate\Support\Facades\Notification;

final readonly class CheckQueueHealth
{
    use BuildsNotificationContent;

    /**
     * @return array{alerted: bool, recovered: bool, reasons: array<int, string>}
     */
    public function execute(): array
    {
        $reasons = $this->reasons();

        if ($reasons === []) {
            return [
                'alerted' => false,
                'recovered' => $this->recover(),
                'reasons' => [],
            ];
        }

        if (! Cache::add('queue-health:alerted', true, now()->addHour())) {
            return ['alerted' => false, 'recovered' => false, 'reasons' => $reasons];
        }

        Cache::put('queue-health:failed-jobs-seen-at', now(), now()->addDay());

        Notification::sendNow(new AdminNotifiable, new QueueStalled($reasons));

        return ['alerted' => true, 'recovered' => false, 'reasons' => $reasons];
    }

    /**
     * @return array<int, string>
     */
    private function reasons(): array
    {
        $reasons = [];

        $stalledSince = now()->subMinutes(
            config()->integer('monitoring.queue_stall_threshold_minutes'),
        );

        $waiting = DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $stalledSince->getTimestamp())
            ->count();

        if ($waiting > 0) {
            $reasons[] = "{$waiting} job(s) waiting in the queue since before {$this->localTime($stalledSince)}.";
        }

        $stalled = Project::query()
            ->whereNotNull('pending_issue_notifications')
            ->get()
            ->filter(fn (Project $project): bool => $this->hasStalledPending($project, $stalledSince));

        if ($stalled->isNotEmpty()) {
            $names = $stalled->map(fn (Project $project): string => $project->name)->implode(', ');

            $reasons[] = "Issue notifications stuck in the queue for {$names}.";
        }

        $seenAt = Cache::get('queue-health:failed-jobs-seen-at');

        $failedSince = $seenAt instanceof CarbonInterface ? $seenAt : now()->subHour();

        $failed = DB::table('failed_jobs')->where('failed_at', '>', $failedSince)->count();

        if ($failed > 0) {
            $reasons[] = "{$failed} job(s) failed since {$this->localTime($failedSince)}.";
        }

        return $reasons;
    }

    private function hasStalledPending(Project $project, CarbonInterface $stalledSince): bool
    {
        foreach ($project->pending_issue_notifications ?? [] as $entry) {
            $queuedAt = $entry['queued_at'] ?? null;

            if (is_string($queuedAt) && now()->parse($queuedAt)->lt($stalledSince)) {
                return true;
            }
        }

        return false;
    }

    private function recover(): bool
    {
        Cache::forget('queue-health:failed-jobs-seen-at');

        if (! Cache::pull('queue-health:alerted')) {
            return false;
        }

        Notification::sendNow(new AdminNotifiable, new QueueRecovered);

        return true;
    }
}
