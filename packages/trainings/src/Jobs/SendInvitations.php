<?php

namespace Moox\Training\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Moox\Jobs\Traits\JobProgress;
use Moox\Training\Mail\Invitation;
use Moox\Training\Models\TrainingInvitation;

class SendInvitations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    public $invitationId;

    public function __construct($invitationId)
    {
        $this->tries = 3;
        $this->timeout = 300;
        $this->maxExceptions = 1;
        $this->backoff = 350;
        $this->invitationId = $invitationId;
    }

    public function handle()
    {
        $this->setProgress(1);

        $trainingDates = [];

        //
        $invitation = TrainingInvitation::find($this->invitationId);

        $invitation->trainingDates()
            ->whereNull('sent_at')
            ->get()
            ->each(function ($trainingDate) {
                $trainingDates[] = $trainingDate;
                $trainingDate->update(['sent_at' => now()]);
            });

        $email = 'alf@drollinger.info';

        Log::info('Sending invitation to '.$email.' for '.count($trainingDates).' training dates');

        Mail::to($email)->send(new Invitation($trainingDates));

        $this->setProgress(100);
    }
}
