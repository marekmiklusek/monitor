<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\CheckHeartbeats;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('monitor:check-heartbeats')]
#[Description('Alert on projects that stopped sending heartbeats and notify once they recover')]
final class CheckHeartbeatsCommand extends Command
{
    public function handle(CheckHeartbeats $checkHeartbeats): int
    {
        $result = $checkHeartbeats->execute();

        $this->components->info("Alerted {$result['alerted']} projects and recovered {$result['recovered']}.");

        return self::SUCCESS;
    }
}
