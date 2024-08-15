<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Sync\Http\Resources\SyncResource;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;

class PlatformSyncController extends Controller
{
    public function index($platformId)
    {
        $syncs = Sync::whereHas('sourcePlatform', function ($query) use ($platformId) {
            $query->where('id', $platformId);
        })->with(['sourcePlatform', 'targetPlatform'])->get();

        return response()->json(SyncResource::collection($syncs), 200);
    }

    // GET /api/platform/{platform}/sync/{sync}
    public function show(Platform $platform, Sync $sync)
    {
        // Ensure the sync belongs to the platform
        if ($sync->source_platform_id !== $platform->id && $sync->target_platform_id !== $platform->id) {
            abort(404, 'Sync not found for this platform.');
        }

        return response()->json(new SyncResource($sync), 200);
    }

    // POST /api/platform/{platform}/sync
    public function store(Request $request, Platform $platform)
    {
        // Optionally, validate request data
        $validated = $request->validate([
            'status' => 'required|boolean',
            'title' => 'required|string|max:255',
            'source_model' => 'required|string|max:255',
            'target_model' => 'required|string|max:255',
            // Add other validation rules as needed
        ]);

        // Create a new sync for this platform
        $sync = new Sync($validated);
        $sync->source_platform_id = $platform->id; // or set target_platform_id depending on your logic
        $sync->save();

        return response()->json(new SyncResource($sync), 201);
    }

    // PUT/PATCH /api/platform/{platform}/sync/{sync}
    public function update(Request $request, Platform $platform, Sync $sync)
    {
        // Ensure the sync belongs to the platform
        if ($sync->source_platform_id !== $platform->id && $sync->target_platform_id !== $platform->id) {
            abort(404, 'Sync not found for this platform.');
        }

        // Update the sync with request data
        $sync->update($request->all());

        return response()->json(new SyncResource($sync), 200);
    }

    // DELETE /api/platform/{platform}/sync/{sync}
    public function destroy(Platform $platform, Sync $sync)
    {
        // Ensure the sync belongs to the platform
        if ($sync->source_platform_id !== $platform->id && $sync->target_platform_id !== $platform->id) {
            abort(404, 'Sync not found for this platform.');
        }

        // Delete the sync
        $sync->delete();

        return response()->json(null, 204);
    }
}
