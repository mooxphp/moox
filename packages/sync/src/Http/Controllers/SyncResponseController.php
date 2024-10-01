<?php

namespace Moox\Sync\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Jobs\FileSyncJob;
use Moox\Sync\Models\Platform;

class SyncResponseController extends Controller
{
    use LogLevel;

    public function sync(Request $request)
    {
        $this->logInfo('Sync Response received', ['data' => $request->all()]);

        $validatedData = $request->validate([
            'model_class' => 'required|string',
            'model_id' => 'required',
            'sync_status' => 'required|string|in:success,error',
            'message' => 'nullable|string',
            'missing_files' => 'nullable|array',
            'target_platform_id' => 'required|exists:platforms,id',
        ]);

        if ($validatedData['sync_status'] === 'success' && ! empty($validatedData['missing_files'])) {
            $this->handleMissingFiles($validatedData, $request);
        }

        return response()->json(['message' => 'Sync response processed']);
    }

    protected function handleMissingFiles(array $data, Request $request)
    {
        $sourcePlatform = Platform::where('domain', $request->getHost())->firstOrFail();
        $targetPlatform = Platform::findOrFail($data['target_platform_id']);

        foreach ($data['missing_files'] as $field => $fileInfo) {
            FileSyncJob::dispatch(
                $data['model_class'],
                $data['model_id'],
                $field,
                $fileInfo,
                $sourcePlatform,
                $targetPlatform
            );
        }
    }
}
