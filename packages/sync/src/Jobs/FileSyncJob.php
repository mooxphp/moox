<?php

namespace Moox\Sync\Jobs;

use Exception;
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
    use Dispatchable;
    use InteractsWithQueue;
    use LogLevel;
    use Queueable;
    use SerializesModels;
    public function __construct(protected $modelClass, protected $modelId, protected $field, protected $fileData, protected Platform $sourcePlatform, protected Platform $targetPlatform)
    {
    }

    public function handle(): void
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
        } catch (Exception $exception) {
            Log::error('File sync failed', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'field' => $this->field,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    protected function validateFile(): bool
    {
        $allowedExtensions = config('sync.file_sync_allowed_extensions', []);
        $extension = pathinfo((string) $this->fileData['path'], PATHINFO_EXTENSION);

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
        $chunkSize = config('sync.file_sync_chunk_size_http', 1024 * 1024); // Default to 1MB
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

    protected function sendChunk($chunk, string $chunkIndex, $totalChunks)
    {
        $url = sprintf('https://%s/api/file-sync/chunk', $this->targetPlatform->domain);

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->post($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
            'chunk_index' => $chunkIndex,
            'total_chunks' => $totalChunks,
            'chunk' => base64_encode((string) $chunk),
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to send chunk ' . $chunkIndex);
        }
    }

    protected function finalizeFileSync()
    {
        $url = sprintf('https://%s/api/file-sync/finalize', $this->targetPlatform->domain);

        $response = Http::withHeaders([
            'X-Platform-Token' => $this->targetPlatform->api_token,
        ])->post($url, [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'field' => $this->field,
            'file_data' => $this->fileData,
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to finalize file sync');
        }
    }

    protected function checkTargetFileExists(): bool
    {
        $url = sprintf('https://%s/api/file-sync/check', $this->targetPlatform->domain);

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
        $url = sprintf('https://%s/api/file-sync/size', $this->targetPlatform->domain);

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
        $url = sprintf('https://%s/api/file-sync/hash', $this->targetPlatform->domain);

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
