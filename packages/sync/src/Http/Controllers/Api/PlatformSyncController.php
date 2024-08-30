<?php

namespace Moox\Sync\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Moox\Sync\Http\Resources\SyncResource;
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
}
