<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Moox\Sync\Jobs\SyncJob;
use Moox\Sync\Models\Sync;
use Moox\Core\Traits\LogLevel;

class SyncWebhookController extends Controller
{
    use LogLevel;

    public function __construct()
    {
        Log::info('SyncWebhookController instantiated');
    }

    public function handle(Request $request)
    {
        Log::info('SyncWebhookController handle method entered');
        Log::info('Request data:', $request->all());

        $validatedData = $this->validateRequest($request);

        $sync = Sync::findOrFail($validatedData['sync']['id']);

        $this->verboseLog('Moox Sync: Webhook recieved for sync', ['sync' => $sync->id]);

        SyncJob::dispatch($sync, $validatedData['model'], $validatedData['event_type']);

        return response()->json(['status' => 'success'], 200);
    }

    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'event_type' => 'required|string|in:created,updated,deleted',
            'model' => 'required|array',
            'sync' => 'required|array',
            'sync.id' => 'required|integer|exists:syncs,id',
        ]);
    }
}
