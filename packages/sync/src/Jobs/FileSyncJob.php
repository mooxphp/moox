<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;

class FileSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $modelClass;

    protected $modelId;

    protected $field;

    protected $fileData;

    protected $sourcePlatform;

    protected $targetPlatform;

    public function __construct($modelClass, $modelId, $field, $fileData, Platform $sourcePlatform, Platform $targetPlatform)
    {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->field = $field;
        $this->fileData = $fileData;
        $this->sourcePlatform = $sourcePlatform;
        $this->targetPlatform = $targetPlatform;
    }

    public function handle()
    {
        try {
            if (! $this->validateFile()) {
                $this->logDebug('File validation failed', [
                    'model_class' => $this->modelClass,
                    'model_id' => $this->modelId,
                    'field' => $this->field,
                ]);

                return;
            }

            if (! $this->shouldSyncFile()) {
                return;
            }

            $this->syncFile();

        } catch (\Exception $e) {
            Log::error('File sync failed', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'field' => $this->field,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function validateFile(): bool
    {
        $allowedExtensions = config('sync.file_sync_allowed_extensions', []);
        $extension = pathinfo($this->fileData['path'], PATHINFO_EXTENSION);

        return in_array(strtolower($extension), $allowedExtensions);
    }

    protected function shouldSyncFile(): bool
    {
        $targetFileExists = $this->checkTargetFileExists();
        if (! $targetFileExists) {
            return true;
        }

        $sourceFileSize = $this->fileData['size'];
        $targetFileSize = $this->getTargetFileSize();

        if ($sourceFileSize !== $targetFileSize) {
            return true;
        }

        $sourceFileHash = $this->getSourceFileHash();
        $targetFileHash = $this->getTargetFileHash();

        return $sourceFileHash !== $targetFileHash;
    }

    protected function syncFile()
    {
        $chunkSize = config('sync.file_sync_chunk_size_http', 1 * 1024 * 1024); // Default to 1MB
        $fileSize = $this->fileData['size'];
        $filePath = $this->fileData['path'];

        $totalChunks = ceil($fileSize / $chunkSize);

        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $offset = $chunkIndex * $chunkSize;
            $chunk = file_get_contents($filePath, false, null, $offset, $chunkSize);

            $this->sendChunk($chunk, $chunkIndex, $totalChunks);
        }

        $this->finalizeFileSync();
    }

    protected function sendChunk($chunk, $chunkIndex, $totalChunks)
    {
        $url = "https://{$this->targetPlatform->domain}/api/file-sync/chunk";

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->post($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'chunk' => base64_encode($chunk),
        ]);

        if (! $response->successful()) {
            throw new \Exception("Failed to send chunk {$chunkIndex}");
        }
    }

    protected function finalizeFileSync()
    {
        $url = "https://{$this->targetPlatform->domain}/api/file-sync/finalize";

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->post($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
            'file_data' => $this->fileData,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to finalize file sync');
        }
    }

    protected function checkTargetFileExists(): bool
    {
        $url = "https://{$this->targetPlatform->domain}/api/file-sync/check";

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->get($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
        ]);

        return $response->json('exists', false);
    }

    protected function getTargetFileSize(): int
    {
        $url = "https://{$this->targetPlatform->domain}/api/file-sync/size";

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->get($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
        ]);

        return $response->json('size', 0);
    }

    protected function getSourceFileHash(): string
    {
        return md5_file($this->fileData['path']);
    }

    protected function getTargetFileHash(): string
    {
        $url = "https://{$this->targetPlatform->domain}/api/file-sync/hash";

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->get($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
        ]);

        return $response->json('hash', '');
    }
}
