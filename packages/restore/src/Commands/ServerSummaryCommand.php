<?php

namespace Moox\Restore\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Moox\Restore\Mail\SummaryBackupMail;
use Moox\Restore\Models\RestoreBackup;
use Spatie\BackupServer\Models\Backup;
use Spatie\BackupServer\Tasks\Summary\Actions\CreateServerSummaryAction;

class ServerSummaryCommand extends Command
{
    protected $signature = 'moox-restore:summary';

    protected $description = 'Send Email with Server Summary';

    public function handle()
    {
        $from = Carbon::today()->setHour(0)->setMinutes(0)->setSeconds(0);
        $to = Carbon::today()->setHour(5)->setMinutes(0)->setSeconds(0);
        $summary = new CreateServerSummaryAction;

        $backupsQuery = Backup::query()->where(function (Builder $query) use ($to, $from) {
            $query
                ->whereBetween('completed_at', [$from, $to])
                ->WhereBetween('created_at', [$from, $to]);
        })->get();

        $restoreBackups = RestoreBackup::query()->where(function (Builder $query) use ($to, $from) {
            $query
                ->whereBetween('created_at', [$from, $to]);
        })->get();

        $summaryResult = $summary->execute($from, $to);

        $message = (new SummaryBackupMail([
            'summary' => $summaryResult,
            'backups' => $backupsQuery,
            'restore' => $restoreBackups,
        ]));

        Mail::to('kim.speer@heco.de')->queue($message);
    }
}
