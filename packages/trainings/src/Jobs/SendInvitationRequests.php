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

    public function handle(): void
    {
        $this->setProgress(1);

        $invitationRequests = Training::where('due_at', '<', now())
            ->get()
            ->map(fn ($training) => TrainingInvitation::create([
                'training_id' => $training->id,
                'title' => $training->title,
                'slug' => Str::slug($training->title),
                'content' => $training->description,
                'status' => 'new',
                // TODO: what about this user_id? I forgot ...
                // 'user_id' => $training->users->first()->id,
            ]));

        $this->setProgress(10);

        foreach ($invitationRequests as $invitationRequest) {
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

    // TODO: Dynamic user model, not hardcoded, Stan ...
    /*
    protected function getUserEmailById($userId)
    {

        $user = WpUser::find($userId);
        if ($user) {
            return $user->user_email;
        }

        return null;
    }
    */
}
