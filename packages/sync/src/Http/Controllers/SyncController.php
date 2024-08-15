<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Sync\Http\Resources\SyncResource;
use Moox\Sync\Models\Sync;

class SyncController extends Controller
{
    public function index()
    {
        $syncs = Sync::all();

        return SyncResource::collection($syncs);
    }

    public function show($id)
    {
        $sync = Sync::findOrFail($id);

        return new SyncResource($sync);
    }

    public function store(Request $request)
    {
        $sync = Sync::create($request->all());

        return new SyncResource($sync);
    }

    public function update(Request $request, $id)
    {
        $sync = Sync::findOrFail($id);
        $sync->update($request->all());

        return new SyncResource($sync);
    }

    public function destroy($id)
    {
        $sync = Sync::findOrFail($id);
        $sync->delete();

        return response()->json(null, 204);
    }
}
