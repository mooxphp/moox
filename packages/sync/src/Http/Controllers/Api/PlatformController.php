<?php

namespace Moox\Sync\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\Sync\Http\Resources\PlatformResource;
use Moox\Sync\Http\Resources\SyncResource;
use Moox\Sync\Models\Platform;

class PlatformController extends Controller
{
    public function index()
    {
        $platforms = Platform::all();

        return PlatformResource::collection($platforms);
    }

    public function show($id): PlatformResource
    {
        $platform = Platform::findOrFail($id);

        return new PlatformResource($platform);
    }

    public function store(Request $request): PlatformResource
    {
        $platform = Platform::create($request->all());

        return new PlatformResource($platform);
    }

    public function update(Request $request, $id): PlatformResource
    {
        $platform = Platform::findOrFail($id);
        $platform->update($request->all());

        return new PlatformResource($platform);
    }

    public function destroy($id)
    {
        $platform = Platform::findOrFail($id);
        $platform->delete();

        return response()->json(null, 204);
    }

    public function syncs(Platform $platform)
    {
        $syncs = $platform->syncs()->get();

        return SyncResource::collection($syncs);
    }
}
