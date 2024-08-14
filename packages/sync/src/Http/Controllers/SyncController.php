<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Sync\Models\Sync;
use Moox\Press\Models\Platform;

class SyncController extends Controller
{
    public function index(Platform $platform)
    {
        // Get all syncs related to the platform
        $syncs = $platform->syncs()->get();
        return SyncResource::collection($syncs);
    }

    public function show(Platform $platform, Sync $sync)
    {
        // Ensure the sync belongs to the platform
        if ($sync->source_platform_id !== $platform->id) {
            return response()->json(['error' => 'Sync not found for this platform'], 404);
        }

        return new SyncResource($sync);
    }

    public function store(Request $request, Platform $platform)
    {
        $sync = new Sync($request->all());
        $sync->source_platform_id = $platform->id;
        $sync->save();

        return new SyncResource($sync);
    }

    public function update(Request $request, Platform $platform, Sync $sync)
    {
        if ($sync->source_platform_id !== $platform->id) {
            return response()->json(['error' => 'Sync not found for this platform'], 404);
        }

        $sync->update($request->all());
        return new SyncResource($sync);
    }

    public function destroy(Platform $platform, Sync $sync)
    {
        if ($sync->source_platform_id !== $platform->id) {
            return response()->json(['error' => 'Sync not found for this platform'], 404);
        }

        $sync->delete();
        return response()->json(null, 204);
    }
}
