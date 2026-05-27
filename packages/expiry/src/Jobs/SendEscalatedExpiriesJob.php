<?php

namespace Moox\Expiry\Jobs;

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
            ->with('notifyUser')
            ->get();

        if ($escalatedExpiries->isEmpty()) {
            return;
        }

        $escalatedEntries = $escalatedExpiries->filter(fn (Expiry $entry): bool => $entry->escalated_at !== null);

        $data = [
            'escalatedEntries' => $escalatedEntries->map(function (Expiry $entry): array {
                return [
                    'title' => $entry->title,
                    'expired_at' => $entry->expired_at?->diffForHumans(),
                    'processing_deadline' => $entry->processing_deadline?->diffForHumans(),
                    'escalated_at' => $entry->escalated_at?->format('d.m.Y'),
                    'notified_to' => $entry->notifyUser?->display_name,
                    'user_email' => $entry->notifyUser?->email,
                    'category' => $entry->category,
                ];
            }),
        ];

        $responsibleEmail = $escalatedExpiries->first()->notifyUser?->email;

        Mail::to($responsibleEmail ?: $adminEmail)
            ->cc($adminEmail)
            ->send(new EscalatedExpiriesMail($data, $panelPath));

        $escalatedEntries->each(function ($entry): void {
            $responsibleId = $entry->notified_to;
            $entry->escalated_to = $responsibleId;
            $entry->save();
        });
    }
}
