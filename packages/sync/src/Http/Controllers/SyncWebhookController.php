<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Sync\Jobs\SyncJob;
use Moox\Sync\Models\Sync;

class SyncWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $validatedData = $this->validateRequest($request);

        $sync = Sync::findOrFail($validatedData['sync']['id']);

        // TODO: Dispatch the SyncJob
        //SyncJob::dispatch($sync, $validatedData['model'], $validatedData['event_type']);

        return response()->json(['status' => 'success'], 200);
    }

    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'event_type' => 'required|string|in:created,updated,deleted',
            'model' => 'required|array',
            'sync' => 'required|array',
            'sync.id' => 'required|integer|exists:syncs,id',
        ]);
    }
}
