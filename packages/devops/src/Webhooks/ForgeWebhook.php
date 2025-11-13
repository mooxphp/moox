<?php

namespace Moox\Devops\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Moox\Devops\Models\MooxProject;

class ForgeWebhook extends Controller
{
    public function handleForge(Request $request)
    {
        $data = $request->all();

        $project = MooxProject::where('site_id', $data['site']['id'])->first();

        if ($project) {
            $user = null;
            if (isset($project->deployed_by_user_id)) {
                $user = User::where('id', $project->deployed_by_user_id)->first();
            }

            if ($data['status'] == 'success') {
                Notification::make()
                    ->title('Project '.$project->name.' has been deployed successfully. You may visit Forge for more details.')
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url('https://forge.laravel.com/servers/'.$project->server_id.'/sites/'.$project->site_id.'/deployments', shouldOpenInNewTab: true),
                    ])
                    ->success()
                    ->broadcast($user);
            } else {
                Notification::make()
                    ->title('Project '.$project->name.' has NOT been deployed! Visit Forge to resolve errors.')
                    ->actions([
                        Action::make('view')
                            ->button()
                            ->url('https://forge.laravel.com/servers/'.$project->server_id.'/sites/'.$project->site_id.'/deployments', shouldOpenInNewTab: true),
                    ])
                    ->body(json_encode($data))
                    ->danger()
                    ->sendToDatabase($user);

                logger()->error('Project '.$project->name.' has NOT been deployed due to errors.');
            }

            $project->update([
                'last_commit_hash' => $data['commit_hash'],
                'last_commit_url' => $data['commit_url'],
                'last_commit_message' => $data['commit_message'],
                'last_commit_author' => $data['commit_author'],
                'deployment_status' => $data['status'],
                'lock_deployments' => false,
                'last_deployment' => now(),
            ]);
        } else {
            logger()->error('Failed to update project: Site ID not found', ['site_id' => $data['site']['id']]);

            return response()->json(['error' => 'Site ID not found'], 404);
        }
    }
}
