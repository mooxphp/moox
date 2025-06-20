<?php

namespace Moox\Devops\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Moox\Devops\Models\MooxProject;
use Moox\Devops\Models\MooxServer;

class SyncForgeData extends Command
{
    protected $signature = 'mooxdevops:syncforge';

    protected $description = 'Synchronize data from Laravel Forge API and Git data';

    public function handle()
    {
        $client = new Client;
        $apiKey = config('devops.forge_api_key');
        $baseUrl = config('devops.forge_api_url');

        $serversResponse = $client->request('GET', $baseUrl.'/servers', [
            'headers' => ['Authorization' => "Bearer {$apiKey}"],
        ]);

        $servers = json_decode($serversResponse->getBody()->getContents(), true);

        foreach ($servers['servers'] as $serverData) {
            if (str_contains($serverData['name'], config('devops.forge_server_filter'))) {
                $server = MooxServer::updateOrCreate(
                    ['forge_id' => $serverData['id']],
                    [
                        'name' => $serverData['name'],
                        'ip_address' => $serverData['ip_address'],
                        'type' => $serverData['type'],
                        'provider' => $serverData['provider'],
                        'region' => $serverData['region'],
                        'ubuntu_version' => $serverData['ubuntu_version'],
                        'db_status' => $serverData['db_status'],
                        'redis_status' => $serverData['redis_status'],
                        'php_version' => $serverData['php_version'],
                        'is_ready' => $serverData['is_ready'],
                    ]
                );

                $projectsResponse = $client->request('GET', $baseUrl.'/servers/'.$serverData['id'].'/sites', [
                    'headers' => ['Authorization' => "Bearer {$apiKey}"],
                ]);

                $projects = json_decode($projectsResponse->getBody()->getContents(), true);

                foreach ($projects['sites'] as $projectData) {
                    $project = MooxProject::updateOrCreate(
                        ['site_id' => $projectData['id']],
                        [
                            'name' => $projectData['name'],
                            'deployment_url' => $projectData['deployment_url'],
                            'server_id' => $projectData['server_id'],
                        ]
                    );
                }
            }
        }

        $this->info('Data synchronization complete.');
    }
}
