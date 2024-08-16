<?php

namespace Moox\Expiry\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Moox\Expiry\Models\Expiry;

class ExpiryController extends Controller
{
    public function index()
    {
        return Expiry::all();
    }

    public function show($id)
    {
        return Expiry::findOrFail($id);
    }

    public function create(Request $request)
    {
        $expiry = Expiry::create($request->all());

        return response()->json($expiry, 201);
    }

    public function update(Request $request, $id)
    {
        $expiry = Expiry::findOrFail($id);
        $expiry->update($request->all());

        return response()->json($expiry, 200);
    }

    public function destroy($id)
    {
        Expiry::destroy($id);

        return response()->json(null, 204);
    }

    public function count()
    {
        return Expiry::count();
    }

    public function countForUser($user)
    {
        return Expiry::where('notified_to', $user)->count();
    }
}
