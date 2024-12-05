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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        if (! config('expiry.send-escalation')) {
            return;
        }

        $adminEmail = config('expiry.send-escalation-copy');
        $panelPath = config('expiry.panel_path');

        $escalatedExpiries = Expiry::query()
            ->where(function ($query) {
                $query->whereNull('escalated_to')
                    ->whereNotNull('escalated_at');
            })
            ->get();

        if ($escalatedExpiries->isEmpty()) {
            return;
        }

        $escalatedEntries = $escalatedExpiries->filter(fn ($entry) => $entry->escalated_at !== null);

        $data = [
            'escalatedEntries' => $escalatedEntries->map(function ($entry) {
                return [
                    'title' => $entry->title,
                    'expired_at' => Carbon::parse($entry->expired_at)->format('d.m.Y'),
                    'processing_deadline' => Carbon::parse($entry->processing_deadline)->format('d.m.Y'),
                    'escalated_at' => Carbon::parse($entry->escalated_at)->format('d.m.Y'),
                    'notified_to' => config('expiry.user_model')::where('ID', $entry->notified_to)->first()?->display_name,
                    'user_email' => config('expiry.user_model')::where('ID', $entry->notified_to)->first()?->email,
                ];
            }),
        ];

        $responsibleEmail = config('expiry.user_model')::where('ID', $escalatedExpiries->first()->notified_to)->first()?->email;

        Mail::to($responsibleEmail)
            ->cc($adminEmail)
            ->send(new EscalatedExpiriesMail($data, $panelPath));

        $escalatedEntries->each(function ($entry) {
            $responsibleId = $entry->notified_to;
            $entry->escalated_to = $responsibleId;
            $entry->save();
        });
    }
}
