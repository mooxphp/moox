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

    public function handle(Request $request)
    {
        $this->logInfo('Moox Sync: WebhookController handle method entered', ['full_request_data' => $request->all()]);

        try {
            $validatedData = $this->validateRequest($request);
            $this->logDebug('Moox Sync: WebhookController validated request', ['validated_data' => $validatedData]);

            $modelId = $this->getModelId($validatedData['model']);
            if (! $modelId) {
                throw new \Exception('No valid identifier found for the model');
            }

            $sourcePlatform = Platform::where('domain', $validatedData['platform']['domain'])->firstOrFail();
            $targetPlatform = Platform::where('domain', $request->getHost())->firstOrFail();

            SyncJob::dispatch(
                $validatedData['model_class'],
                $validatedData['model'],
                $validatedData['event_type'],
                $sourcePlatform,
                $targetPlatform,
                $validatedData['should_delete']
            );

            return response()->json(['status' => 'success', 'message' => 'Sync job dispatched']);
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

        return $request->validate([
            'event_type' => 'required|string|in:created,updated,deleted',
            'model' => 'required|array',
            'model_class' => 'required|string',
            'platform' => 'required|array',
            'platform.domain' => 'required|string',
            'should_delete' => 'required|boolean',
        ]);
    }

    protected function getModelId(array $modelData)
    {
        $localIdentifierFields = config('sync.local_identifier_fields', ['ID', 'uuid', 'ulid', 'id']);

        foreach ($localIdentifierFields as $field) {
            if (isset($modelData[$field])) {
                return [
                    'field' => $field,
                    'value' => $modelData[$field],
                ];
            }
        }

        return null;
    }
}
