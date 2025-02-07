<?php

namespace Moox\Sync\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

            if (isset($validatedData['_file_sync']) && is_array($validatedData['_file_sync'])) {
                $this->handleFileSyncJobs($validatedData, $sourcePlatform, $targetPlatform);
            }

            return response()->json(['status' => 'success', 'message' => 'Sync job dispatched']);
        } catch (Exception $exception) {
            $this->logDebug('SyncWebhookController encountered an error', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 500);
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

    protected function checkForMissingFiles(array $data): array
    {
        $missingFiles = [];
        if (isset($data['_file_sync']) && is_array($data['_file_sync'])) {
            foreach ($data['_file_sync'] as $field => $fileInfo) {
                if (! $this->fileExists($fileInfo['path'])) {
                    $missingFiles[$field] = $fileInfo;
                }
            }
        }

        return $missingFiles;
    }

    protected function fileExists(string $path): bool
    {
        // Implement logic to check if the file exists on the target platform
        // This might involve checking the local filesystem or a remote storage service
        return Storage::exists($path);
    }

    protected function requestMissingFiles(array $data, array $missingFiles, Platform $sourcePlatform)
    {
        $responsePath = config('sync.sync_response_url', '/sync-response');
        $url = sprintf('https://%s%s', $sourcePlatform->domain, $responsePath);

        Http::post($url, [
            'model_class' => $data['model_class'],
            'model_id' => $data['model']['id'],
            'sync_status' => 'success',
            'message' => 'Sync successful, but some files are missing',
            'missing_files' => $missingFiles,
            'target_platform_id' => Platform::where('domain', request()->getHost())->first()->id,
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

    protected function getModelId(array $modelData): ?array
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
