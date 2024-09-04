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
        $this->logDebug('SyncWebhookController handle method entered', ['full_request_data' => $request->all()]);

        try {
            $validatedData = $this->validateRequest($request);

            $this->logDebug('SyncWebhookController validated request', ['validated_data' => $validatedData]);

            // Log specific fields we're interested in
            $this->logDebug('Important fields from validated data', [
                'event_type' => $validatedData['event_type'],
                'model_class' => $validatedData['model_class'],
                'user_registered' => $validatedData['model']['user_registered'] ?? 'not set',
                'capabilities' => $validatedData['model']['jku8u_capabilities'] ?? 'not set',
                'description' => $validatedData['model']['description'] ?? 'not set',
                // Add any other fields you want to check
            ]);

            $sourcePlatform = Platform::where('domain', $validatedData['platform']['domain'])->first();

            if (! $sourcePlatform) {
                throw new \Exception('Source platform not found');
            }

            $modelId = $validatedData['model']['ID'] ?? $validatedData['model']['id'] ?? null;
            if (! $modelId) {
                throw new \Exception('Model ID not found in the request data');
            }

            // Log the exact data being passed to SyncJob
            $this->logDebug('Data being passed to SyncJob', [
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
        $this->logDebug('SyncWebhookController validating request', ['raw_request_data' => $request->all()]);

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
