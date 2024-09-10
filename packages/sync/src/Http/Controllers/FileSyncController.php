<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Moox\Core\Traits\LogLevel;

class FileSyncController extends Controller
{
    use LogLevel;

    protected $tempDirectory;

    public function __construct()
    {
        $this->tempDirectory = config('sync.file_sync_temp_directory', 'temp/file_sync');
    }

    public function receiveChunk(Request $request)
    {
        $this->validateRequest($request);

        $chunkPath = $this->saveTempChunk($request);

        return response()->json(['status' => 'success', 'message' => 'Chunk received']);
    }

    public function finalizeSync(Request $request)
    {
        $this->validateRequest($request);

        $filePath = $this->reassembleFile($request);

        if ($this->isFileExtensionAllowed($filePath)) {
            $this->moveFileToFinalLocation($request, $filePath);
            $this->cleanupTempFiles($request);

            return response()->json(['status' => 'success', 'message' => 'File sync completed']);
        } else {
            $this->cleanupTempFiles($request);

            return response()->json(['status' => 'error', 'message' => 'File extension not allowed'], 400);
        }
    }

    public function checkFileExists(Request $request)
    {
        $this->validateRequest($request);

        $exists = $this->doesFileExist($request);

        return response()->json(['exists' => $exists]);
    }

    public function getFileSize(Request $request)
    {
        $this->validateRequest($request);

        $size = $this->getExistingFileSize($request);

        return response()->json(['size' => $size]);
    }

    public function getFileHash(Request $request)
    {
        $this->validateRequest($request);

        $hash = $this->calculateExistingFileHash($request);

        return response()->json(['hash' => $hash]);
    }

    protected function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model_class' => 'required|string',
            'model_id' => 'required',
            'field' => 'required|string',
            'chunk_index' => 'required_without:file_data|integer',
            'total_chunks' => 'required_without:file_data|integer',
            'chunk' => 'required_without:file_data|string',
            'file_data' => 'required_without:chunk|array',
        ]);

        if ($validator->fails()) {
            $this->logDebug('FileSyncController: Invalid request', ['errors' => $validator->errors()]);
            abort(400, 'Invalid request');
        }
    }

    protected function saveTempChunk(Request $request)
    {
        $chunkPath = $this->getTempChunkPath($request);
        Storage::put($chunkPath, base64_decode($request->input('chunk')));

        return $chunkPath;
    }

    protected function reassembleFile(Request $request)
    {
        $tempFilePath = $this->getTempFilePath($request);
        $totalChunks = $request->input('file_data.total_chunks');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $this->getTempChunkPath($request, $i);
            $chunkContent = Storage::get($chunkPath);
            Storage::append($tempFilePath, $chunkContent);
        }

        return $tempFilePath;
    }

    protected function isFileExtensionAllowed($filePath)
    {
        $allowedExtensions = config('sync.file_sync_allowed_extensions', []);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), $allowedExtensions);
    }

    protected function moveFileToFinalLocation(Request $request, $tempFilePath)
    {
        $finalPath = $this->getFinalFilePath($request);
        Storage::move($tempFilePath, $finalPath);
    }

    protected function cleanupTempFiles(Request $request)
    {
        $totalChunks = $request->input('file_data.total_chunks');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $this->getTempChunkPath($request, $i);
            Storage::delete($chunkPath);
        }
        Storage::delete($this->getTempFilePath($request));
    }

    protected function doesFileExist(Request $request)
    {
        $finalPath = $this->getFinalFilePath($request);

        return Storage::exists($finalPath);
    }

    protected function getExistingFileSize(Request $request)
    {
        $finalPath = $this->getFinalFilePath($request);

        return Storage::exists($finalPath) ? Storage::size($finalPath) : 0;
    }

    protected function calculateExistingFileHash(Request $request)
    {
        $finalPath = $this->getFinalFilePath($request);

        return Storage::exists($finalPath) ? md5(Storage::get($finalPath)) : '';
    }

    protected function getTempChunkPath(Request $request, $chunkIndex = null)
    {
        $chunkIndex = $chunkIndex ?? $request->input('chunk_index');

        return "{$this->tempDirectory}/{$request->input('model_class')}/{$request->input('model_id')}/{$request->input('field')}/chunk_{$chunkIndex}";
    }

    protected function getTempFilePath(Request $request)
    {
        return "{$this->tempDirectory}/{$request->input('model_class')}/{$request->input('model_id')}/{$request->input('field')}/temp_file";
    }

    protected function getFinalFilePath(Request $request)
    {
        // This method should be implemented based on your application's file storage logic
        // For example, you might store files in a specific directory structure based on the model and field
        return "uploads/{$request->input('model_class')}/{$request->input('model_id')}/{$request->input('field')}/{$request->input('file_data.name')}";
    }
}
