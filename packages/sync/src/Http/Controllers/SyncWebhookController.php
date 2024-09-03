<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Jobs\SyncJob;
use Moox\Sync\Models\Sync;

class SyncWebhookController extends Controller
{
    use LogLevel;

    public function __construct()
    {
        $this->logDebug('SyncWebhookController instantiated');
    }

    public function handle(Request $request)
    {
        $this->logDebug('SyncWebhookController handle method entered with data', ['data' => $request->all()]);

        $validatedData = $this->validateRequest($request);

        $this->logDebug('SyncWebhookController validated request', ['data' => $validatedData]);

        $sync = Sync::findOrFail($validatedData['sync']['id']);

        $this->logDebug('SyncWebhookController dispatching SyncJob', ['sync' => $sync->id]);

        SyncJob::dispatch($sync);

        return response()->json(['status' => 'success'], 200);
    }

    protected function validateRequest(Request $request)
    {
        $this->logDebug('SyncWebhookController validating request', ['data' => $request->all()]);

        return $request->validate([
            'event_type' => 'required|string|in:created,updated,deleted',
            'model' => 'required|array',
            'sync' => 'required|array',
            'sync.id' => 'required|integer|exists:syncs,id',
        ]);
    }
}
