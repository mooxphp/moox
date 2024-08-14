<?php

namespace Moox\Training\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Moox\Jobs\Traits\JobProgress;
use Moox\Press\Models\WpUser;
use Moox\Training\Mail\InvitationRequest;
use Moox\Training\Models\Training;
use Moox\Training\Models\TrainingInvitation;

class SendInvitationRequests implements ShouldQueue
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

        /** @disregard Non static method 'create' should not be called statically.intelephense(P1036) */
        $invitationRequests = Training::where('due_at', '<', now())
            ->get()
            ->map(function ($training) {
                /** @disregard Non static method 'create' should not be called statically.intelephense(P1036) */
                return TrainingInvitation::create([
                    'training_id' => $training->id,
                    'title' => $training->title,
                    'slug' => Str::slug($training->title),
                    'content' => $training->description,
                    'status' => 'new',
                    // 'user_id' => $training->users->first()->id,
                ]);
            });

        $this->setProgress(10);

        foreach ($invitationRequests as $invitationRequest) {

            /** @disregard Non static method 'create' should not be called statically.intelephense(P1036) */
            $training = Training::find($invitationRequest->training_id);

            $cycle = $training->cycle;

            $dueAt = $training->due_at;

            switch ($cycle) {
                case 'annually':
                    $dueAt->addYear();
                    break;
                case 'half-yearly':
                    $dueAt->addMonths(6);
                    break;
                case 'quarterly':
                    $dueAt->addMonths(3);
                    break;
                case 'monthly':
                    $dueAt->addMonth();
                    break;
                case 'every 2 years':
                    $dueAt->addYears(2);
                    break;
                case 'every 3 years':
                    $dueAt->addYears(3);
                    break;
                case 'every 4 years':
                    $dueAt->addYears(4);
                    break;
                case 'every 5 years':
                    $dueAt->addYears(5);
                    break;
            }

            $training->due_at = $dueAt;
            $training->save();

            $this->setProgress(30);

            // $userEmail = $this->getUserEmailById($invitationRequest->user_id);

            $userEmail = 'alf@drollinger.info';

            Mail::to($userEmail)->send(new InvitationRequest($invitationRequest));
        }

        $this->setProgress(100);
    }

    protected function getUserEmailById($userId)
    {
        /** @disregard Non static method 'create' should not be called statically.intelephense(P1036) */
        $user = WpUser::find($userId);
        if ($user) {
            return $user->user_email;
        }

        return null;
    }
}
