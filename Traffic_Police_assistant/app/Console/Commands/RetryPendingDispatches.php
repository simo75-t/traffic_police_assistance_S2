<?php

namespace App\Console\Commands;

use App\Http\Services\Dispatch\DispatchService;
use Illuminate\Console\Command;

class RetryPendingDispatches extends Command
{
    protected $signature = 'dispatch:retry-pending {--limit=50 : Maximum number of pending reports to retry in one run}';

    protected $description = 'Retry dispatching citizen reports that are still unassigned.';

    public function handle(DispatchService $dispatchService): int
    {
        $retried = $dispatchService->dispatchPendingReports(
            max(1, (int) $this->option('limit'))
        );

        $this->info("Retried dispatch for {$retried} pending report(s).");

        return self::SUCCESS;
    }
}
