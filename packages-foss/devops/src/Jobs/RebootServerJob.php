<?php

namespace Moox\Devops\Jobs;

use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Devops\Models\MooxServer;

class RebootServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $server;

    protected $pings;

    protected $user;

    public function __construct(MooxServer $server, $pings, $user)
    {
        $this->server = $server;
        $this->pings = $pings;
        $this->user = $user;
    }

    public function handle()
    {
        if ($this->pingServer($this->server->ip_address)) {
            Log::info("Server {$this->server->name} will be rebooted.");

            Notification::make()
                ->title('Server '.$this->server->name.' will be rebooted.')
                ->success()
                ->broadcast($this->user);
        } else {
            Log::info("Server {$this->server->name} is unavailable and will be rebooted.");

            Notification::make()
                ->title('Server '.$this->server->name.' is unavailable and will be rebooted.')
                ->warning()
                ->broadcast($this->user);
        }

        $this->sendRebootRequest($this->server->forge_id);

        while ($this->pingServer($this->server->ip_address)) {
            sleep(5);
        }
        Log::info("Server {$this->server->name} is rebooting.");

        while (! $this->pingServer($this->server->ip_address)) {
            sleep(5);
            $this->pings++;
            if ($this->pings > 36) {
                Log::info("Server {$this->server->name} is not coming back up after 3 minutes.");

                Notification::make()
                    ->title('Server '.$this->server->name.' is not coming back up after 3 minutes.')
                    ->success()
                    ->broadcast($this->user);

                return;
            }
        }
        Log::info("Server {$this->server->name} is back up.");

        Notification::make()
            ->title('Server '.$this->server->name.' is back up.')
            ->success()
            ->broadcast($this->user);

        $this->server->last_reboot = now();
        $this->server->save();
    }

    protected function pingServer($ipAddress)
    {
        $output = shell_exec("ping -c 1 $ipAddress");

        return strpos($output, '1 received') !== false;
    }

    protected function sendRebootRequest($forgeId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('your-config.forge_api_key'),
            'Accept' => 'application/json',
        ])->post("https://forge.laravel.com/api/v1/servers/{$forgeId}/reboot");

        return $response->successful();
    }
}
