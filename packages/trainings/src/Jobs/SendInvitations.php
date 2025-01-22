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
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var int
     */
    public $timeout = 300;

    /**
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * @var int
     */
    public $backoff = 350;

    public function __construct(public $invitationId) {}

    public function handle(): void
    {
        $this->setProgress(1);

        $trainingDates = [];

        //
        $invitation = TrainingInvitation::find($this->invitationId);

        $invitation->trainingDates()
            ->whereNull('sent_at')
            ->get()
            ->each(function ($trainingDate): void {
                $trainingDates[] = $trainingDate;
                $trainingDate->update(['sent_at' => now()]);
            });

        $email = 'alf@drollinger.info';

        Log::info('Sending invitation to '.$email.' for '.count($trainingDates).' training dates');

        Mail::to($email)->send(new Invitation($trainingDates));

        $this->setProgress(100);
    }
}
