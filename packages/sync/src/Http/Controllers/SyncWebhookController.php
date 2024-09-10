<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Jobs\FileSyncJob;
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

            $sourcePlatform = Platform::where('domain', $validatedData['platform']['domain'])->firstOrFail();
            $targetPlatform = Platform::where('domain', $request->getHost())->firstOrFail();

            $syncJob = SyncJob::dispatch(
                $validatedData['model_class'],
                $validatedData['model'],
                $validatedData['event_type'],
                $sourcePlatform,
                $targetPlatform,
                $validatedData['should_delete']
            );

            $this->handleFileSyncJobs($validatedData, $sourcePlatform, $targetPlatform);

            return response()->json(['status' => 'success', 'message' => 'Sync jobs dispatched']);
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
            '_file_sync' => 'sometimes|array',
        ]);
    }

    protected function handleFileSyncJobs(array $validatedData, Platform $sourcePlatform, Platform $targetPlatform)
    {
        if (! isset($validatedData['_file_sync']) || ! is_array($validatedData['_file_sync'])) {
            return;
        }

        foreach ($validatedData['_file_sync'] as $field => $fileData) {
            if ($this->shouldSyncFile($fileData)) {
                FileSyncJob::dispatch(
                    $validatedData['model_class'],
                    $this->getModelId($validatedData['model']),
                    $field,
                    $fileData,
                    $sourcePlatform,
                    $targetPlatform
                );
            }
        }
    }

    protected function shouldSyncFile(array $fileData): bool
    {
        // TODO: Implement logic to determine if the file should be synced
        // For example, check if the file exists on the target platform and compare file sizes or modification dates
        return true; // For now, always sync the file
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
