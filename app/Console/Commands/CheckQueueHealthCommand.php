<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\CheckQueueHealth;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('monitor:check-queue-health')]
#[Description('Alert when queued issue notifications stop going out, and notify once they resume')]
final class CheckQueueHealthCommand extends Command
{
    public function handle(CheckQueueHealth $checkQueueHealth): int
    {
        $result = $checkQueueHealth->execute();

        if ($result['alerted']) {
            $this->components->error(implode(' ', $result['reasons']));

            return self::SUCCESS;
        }

        if ($result['recovered']) {
            $this->components->info('The queue is processing issue notifications again.');

            return self::SUCCESS;
        }

        $this->components->info(
            $result['reasons'] === [] ? 'The queue is healthy.' : 'The queue is still stalled.',
        );

        return self::SUCCESS;
    }
}
