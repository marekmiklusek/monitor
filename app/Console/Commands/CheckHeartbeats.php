<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use App\Actions\CheckHeartbeats as CheckHeartbeatsAction;

#[Signature('monitor:check-heartbeats')]
#[Description('Alert on projects that stopped sending heartbeats and notify once they recover')]
final class CheckHeartbeats extends Command
{
    public function handle(CheckHeartbeatsAction $checkHeartbeats): int
    {
        $checkHeartbeats->execute();

        return self::SUCCESS;
    }
}
