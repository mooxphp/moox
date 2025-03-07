<?php

namespace Moox\Devops\Jobs;

use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Moox\Devops\Models\MooxProject;

class DeployProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;

    protected $user;

    public function __construct(MooxProject $project, $user)
    {
        $this->project = $project;
        $this->user = $user;
    }

    public function handle()
    {
        $this->project->update([
            'deployment_status' => 'running',
            'deployed_by_user_id' => $this->user->id,
            'lock_deployments' => true,
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('devops.forge_api_key'),
            'Accept' => 'application/json',
        ])->post($this->project->deployment_url);

        Notification::make()
            ->title('Project '.$this->project->name.' will now be deployed.')
            ->success()
            ->broadcast($this->user);
    }
}
