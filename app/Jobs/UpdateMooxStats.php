<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UpdateMooxStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $data = [
            'github' => $this->fetchGitHubStats(),
            'packagist' => $this->fetchPackagistStats(),
            'vscode' => $this->fetchVSCodeStats(),
        ];

        Storage::disk('local')->put('moox_stats.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    private function fetchGitHubStats()
    {
        $githubOrg = config('app.github_org');
        $githubToken = config('app.github_token');

        $response = Http::withToken($githubToken)->get("https://api.github.com/orgs/$githubOrg/repos");

        if (! $response->successful()) {
            return ['error' => 'Failed to fetch GitHub data'];
        }

        $repos = $response->json();
        $stats = [];

        foreach ($repos as $repo) {
            $stats[$repo['name']] = [
                'stars' => $repo['stargazers_count'],
                'forks' => $repo['forks_count'],
                'watchers' => $repo['watchers_count'],
            ];
        }

        return [
            'total_repos' => count($repos),
            'repos' => $stats,
        ];
    }

    private function fetchPackagistStats()
    {
        $vendor = config('app.packagist_vendor');

        $response = Http::get("https://packagist.org/packages/list.json?vendor=$vendor");

        if (! $response->successful()) {
            return ['error' => 'Failed to fetch Packagist data'];
        }

        $packages = $response->json()['packageNames'] ?? [];
        $stats = [];

        foreach ($packages as $package) {
            $packageName = str_replace("$vendor/", '', $package);
            $packageData = Http::get("https://packagist.org/packages/$package.json");

            if ($packageData->successful()) {
                $info = $packageData->json()['package'];
                $stats[$packageName] = [
                    'downloads' => $info['downloads']['total'] ?? 0,
                    'favers' => $info['favers'] ?? 0,
                ];
            }
        }

        return [
            'total_packages' => count($packages),
            'packages' => $stats,
        ];
    }

    private function fetchVSCodeStats()
    {
        $extensionId = config('app.vscode_extension_id');

        $response = Http::post('https://marketplace.visualstudio.com/_apis/public/gallery/extensionquery', [
            'filters' => [
                [
                    'criteria' => [
                        ['filterType' => 7, 'value' => $extensionId],
                    ],
                ],
            ],
            'flags' => 914,
        ]);

        if (! $response->successful()) {
            return ['error' => 'Failed to fetch VS Code data'];
        }

        $data = $response->json();
        $extensions = $data['results'][0]['extensions'] ?? [];

        if (empty($extensions)) {
            return ['error' => 'No VS Code extension data found'];
        }

        return [
            'downloads' => $extensions[0]['statistics'][0]['value'] ?? 0,
        ];
    }
}
