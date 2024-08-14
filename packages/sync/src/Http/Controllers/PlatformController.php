<?php

namespace Moox\Sync\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Sync\Http\Resources\PlatformResource;
use Moox\Sync\Models\Platform;

class PlatformController extends Controller
{
    public function index()
    {
        // Eager load the sources and targets relationships
        $platforms = Platform::with(['sources', 'targets'])->get();
        return PlatformResource::collection($platforms);
    }

    public function show(Platform $platform)
    {
        // Eager load the sources and targets relationships for a single platform
        $platform->load(['sources', 'targets']);
        return new PlatformResource($platform);
    }

    public function store(Request $request)
    {
        // Validation and storing logic
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'order' => 'integer',
            'show_in_menu' => 'boolean',
            'read_only' => 'boolean',
            'locked' => 'boolean',
            'lock_reason' => 'string|nullable',
            'master' => 'boolean',
            'thumbnail' => 'string|nullable',
            'api_token' => 'string|nullable',
        ]);

        $platform = Platform::create($validatedData);
        return new PlatformResource($platform);
    }

    public function update(Request $request, Platform $platform)
    {
        // Validation and update logic
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'domain' => 'sometimes|required|string|max:255',
            'ip_address' => 'sometimes|required|ip',
            'order' => 'integer',
            'show_in_menu' => 'boolean',
            'read_only' => 'boolean',
            'locked' => 'boolean',
            'lock_reason' => 'string|nullable',
            'master' => 'boolean',
            'thumbnail' => 'string|nullable',
            'api_token' => 'string|nullable',
        ]);

        $platform->update($validatedData);
        return new PlatformResource($platform);
    }

    public function destroy(Platform $platform)
    {
        $platform->delete();
        return response()->json(null, 204);
    }
}
