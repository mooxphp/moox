<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Jobs\SyncJob;
use Moox\Sync\Models\Platform;

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

        try {
            $validatedData = $this->validateRequest($request);

            $this->logDebug('SyncWebhookController validated request', ['data' => $validatedData]);

            $sourcePlatform = Platform::where('domain', $validatedData['platform']['domain'])->first();

            if (! $sourcePlatform) {
                throw new \Exception('Source platform not found');
            }

            SyncJob::dispatch($validatedData['model_class'], $validatedData['model'], $validatedData['event_type'], $sourcePlatform);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            $this->logDebug('SyncWebhookController encountered an error', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    protected function validateRequest(Request $request)
    {
        $this->logDebug('SyncWebhookController validating request', ['data' => $request->all()]);

        return $request->validate([
            'event_type' => 'required|string|in:created,updated,deleted',
            'model' => 'required|array',
            'model_class' => 'required|string',
            'platform' => 'required|array',
            'platform.domain' => 'required|string',
        ]);
    }
}
