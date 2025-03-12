<?php

namespace Moox\Expiry\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Moox\Expiry\Mail\EscalatedExpiriesMail;
use Moox\Expiry\Models\Expiry;

class SendEscalatedExpiriesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        if (! config('expiry.send-escalation')) {
            return;
        }

        $adminEmail = config('expiry.send-escalation-copy');
        $panelPath = config('expiry.panel_path');

        $escalatedExpiries = Expiry::query()
            ->where(function ($query): void {
                $query->whereNull('escalated_to')
                    ->whereNotNull('escalated_at');
            })
            ->get();

        if ($escalatedExpiries->isEmpty()) {
            return;
        }

        $escalatedEntries = [];

        $escalatedEntries = $escalatedExpiries->filter(fn ($entry): bool => $entry->escalated_at !== null);

        $data = [
            'escalatedEntries' => $escalatedEntries->map(fn ($entry): array => [
                'title' => $entry->title,
                'expired_at' => Carbon::parse($entry->expired_at)->diffForHumans(),
                'processing_deadline' => Carbon::parse($entry->processing_deadline)->diffForHumans(),
                'escalated_at' => Carbon::parse($entry->escalated_at)->format('d.m.Y'),
                'notified_to' => config('expiry.user_model')::where('ID', $entry->notified_to)->first()?->display_name,
                'user_email' => config('expiry.user_model')::where('ID', $entry->notified_to)->first()?->email,
                'category' => $entry->category,
            ]),
        ];

        $responsibleEmail = config('expiry.user_model')::where('ID', $escalatedExpiries->first()->notified_to)->first()?->email;

        Mail::to($responsibleEmail)
            ->cc($adminEmail)
            ->send(new EscalatedExpiriesMail($data, $panelPath));

        $escalatedEntries->each(function ($entry): void {
            $responsibleId = $entry->notified_to;
            $entry->escalated_to = $responsibleId;
            $entry->save();
        });
    }
}
