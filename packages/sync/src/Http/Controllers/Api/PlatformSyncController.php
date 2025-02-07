<?php

namespace Moox\Sync\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Moox\Sync\Http\Resources\SyncResource;
use Moox\Sync\Models\Sync;

class PlatformSyncController extends Controller
{
    public function index($platformId)
    {
        $syncs = Sync::whereHas('sourcePlatform', function ($query) use ($platformId): void {
            $query->where('id', $platformId);
        })->with(['sourcePlatform', 'targetPlatform'])->get();

        return response()->json(SyncResource::collection($syncs), 200);
    }
}
