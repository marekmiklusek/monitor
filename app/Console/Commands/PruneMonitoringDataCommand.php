<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\PruneMonitoringData;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('monitor:prune')]
#[Description('Delete stale issues and occurrences beyond the retention window')]
final class PruneMonitoringDataCommand extends Command
{
    public function handle(PruneMonitoringData $pruneMonitoringData): int
    {
        $pruned = $pruneMonitoringData->execute();

        $this->components->info("Pruned {$pruned['issues']} issues and {$pruned['occurrences']} occurrences.");

        return self::SUCCESS;
    }
}
