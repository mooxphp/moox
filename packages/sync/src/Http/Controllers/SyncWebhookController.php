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
        $this->logInfo('Moox Sync: WebhookController instantiated');
    }

    public function handle(Request $request)
    {
        $this->logInfo('Moox Sync: WebhookController handle method entered', ['full_request_data' => $request->all()]);

        try {
            $validatedData = $this->validateRequest($request);

            $this->logDebug('Moox Sync: WebhookController validated request', ['validated_data' => $validatedData]);

            $sourcePlatform = Platform::where('domain', $validatedData['platform']['domain'])->first();

            if (! $sourcePlatform) {
                throw new \Exception('Source platform not found');
            }

            $modelId = $validatedData['model']['ID'] ?? $validatedData['model']['id'] ?? null;
            if (! $modelId) {
                throw new \Exception('Model ID not found in the request data');
            }

            $this->logInfo('Data being passed to SyncJob', [
                'model_class' => $validatedData['model_class'],
                'model_data' => $validatedData['model'],
                'event_type' => $validatedData['event_type'],
                'source_platform_id' => $sourcePlatform->id,
            ]);

            SyncJob::dispatch(
                $validatedData['model_class'],
                $validatedData['model'],
                $validatedData['event_type'],
                $sourcePlatform
            );

            $this->logDebug('SyncJob dispatched', [
                'model_class' => $validatedData['model_class'],
                'model_id' => $modelId,
                'event_type' => $validatedData['event_type'],
                'source_platform' => $sourcePlatform->id,
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            $this->logDebug('SyncWebhookController encountered an error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    protected function validateRequest(Request $request)
    {
        $this->logInfo('SyncWebhookController validating request', ['raw_request_data' => $request->all()]);

        $validatedData = $request->validate([
            'event_type' => 'required|string|in:created,updated,deleted',
            'model' => 'required|array',
            'model_class' => 'required|string',
            'platform' => 'required|array',
            'platform.domain' => 'required|string',
        ]);

        $this->logDebug('Request validation result', ['validated_data' => $validatedData]);

        return $validatedData;
    }
}
