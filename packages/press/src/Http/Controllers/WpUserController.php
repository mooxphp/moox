<?php

namespace Moox\Press\Http\Controllers;

use Illuminate\Http\Request;
use Moox\Press\Models\WpUser;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Moox\Press\Http\Resources\WpUserResource;

class WpUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = WpUser::all();
        return WpUserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_login' => 'required|string|max:255',
            'user_pass' => 'required|string|max:255',
            'user_nicename' => 'required|string|max:255',
            'user_email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wpUser = new WpUser();

        $wpUser->fill($request->all());

        $wpUser->save();

        return new WpUserResource($wpUser);
    }

    /**
     * Display the specified resource.
     *
     * @param \Moox\Press\Models\WpUser $wpUser
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new WpUserResource(WpUser::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Moox\Press\Models\WpUser $wpUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $wpUser = new WpUser();
        $wpUserData = $request->only($wpUser->getFillable());
        $wpUserMeta = $request->except($wpUser->getFillable());


        $validator = Validator::make($request->all(), [
            'user_login' => 'sometimes|string|max:255',
            'user_pass' => 'sometimes|string|max:255',
            'user_nicename' => 'sometimes|string|max:255',
            'user_email' => 'sometimes|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wpUser = WpUser::findOrFail($id);

        $wpUser->update($request->all());

        return new WpUserResource($wpUser);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Moox\Press\Models\WpUser $wpUser
     * @return \Illuminate\Http\Response
     */
    public function destroy($wpUser)
    {
        $wpUser = WpUser::findOrFail($wpUser);
        $wpUser->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
