<?php

declare(strict_types=1);

namespace Moox\EBilling\Console\Commands;

use Illuminate\Console\Command;
use Moox\EBilling\Jobs\ScanOverdueApprovalEscalationJob;

final class ScanOverdueApprovalEscalationCommand extends Command
{
    protected $signature = 'e-billing:scan-overdue-approval-escalation';

    protected $description = 'Dispatch ScanOverdueApprovalEscalationJob to notify for pending documents past escalation thresholds';

    public function handle(): int
    {
        ScanOverdueApprovalEscalationJob::dispatch();

        $this->info('Dispatched ScanOverdueApprovalEscalationJob (pending past threshold → NotifyDocumentsNeedReviewJob).');

        return self::SUCCESS;
    }
}
