<?php

namespace Moox\Sync\Http\Controllers\Api;

use Moox\Sync\Models\Sync;
use Illuminate\Routing\Controller;
use Moox\Sync\Http\Resources\SyncResource;

class PlatformSyncController extends Controller
{
    public function index($platformId)
    {

        $syncs = Sync::whereHas('sourcePlatform', function ($query) use ($platformId) {
            $query->where('id', $platformId);
        })->with(['sourcePlatform', 'targetPlatform'])->get();

        return response()->json(SyncResource::collection($syncs), 200);
    }
}
