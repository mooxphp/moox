<?php

namespace Moox\Expiry\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Moox\Expiry\Mail\ExpirySummary;
use Moox\Expiry\Models\Expiry;
use Moox\Jobs\Traits\JobProgress;
use Moox\Press\Models\WpUser;

class SendSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    public function __construct()
    {
        $this->tries = 3;
        $this->timeout = 300;
        $this->maxExceptions = 1;
        $this->backoff = 350;
    }

    public function handle()
    {

        $this->setProgress(1);

        // Get all Expiry Records grouped by user_id
        // Render them in to a mail template
        // Send the mail to the user

        $expiries = Expiry::all()->groupBy('user_id');

        foreach ($expiries as $userId => $userExpiries) {
            $userEmail = $this->getUserEmailById($userId);

            Mail::to($userEmail)->send(new ExpirySummary($userExpiries));

        }

        $this->setProgress(100);
    }

    protected function getUserEmailById($userId)
    {
        /** @disregard Non static method 'find' should not be called statically.intelephense(P1036) */
        $user = WpUser::find($userId);
        if ($user) {
            // TODO: fix this! This is not working since the user_email is not in the fillable array???
            // or provided by the model like it should
            /** @phpstan-ignore-next-line */
            return $user->user_email;
        }

        return null;
    }
}
