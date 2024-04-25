<?php

namespace Moox\LoginLink\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Moox\LoginLink\Mail\LoginLinkEmail;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkController extends Controller
{
    public function requestForm()
    {
        return view('login-link::request');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $result = $this->findUserByEmail($request->email);
        if (! $result) {
            return back()->withErrors(['email' => 'No user found with this email.']);
        }

        $user = $result->user;

        $token = Str::random(40);

        $loginLink = LoginLink::create([
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'email' => $user->email,
            'token' => $token,
            'expires_at' => now()->addHours(config('login-link.expiration_time')),
        ]);

        Mail::to($user->email)->send(new LoginLinkEmail($loginLink));

        return back()->with('message', 'Login link has been sent!');
    }

    public function authenticate($userId, $token)
    {
        try {
            $userId = urldecode(decrypt($userId));
        } catch (DecryptException $e) {
            return redirect()->route('login')->withErrors(['invalid' => 'Invalid or corrupted link.']);
        }

        $loginLink = LoginLink::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('user_id', $userId)
            ->firstOrFail();

        // Todo: fix the model
        if (isset($loginLink->user_type)) {
            $userType = $loginLink->user_type;
        } else {
            $userType = 'App\Models\User';
        }

        $userModel = Config::get('login-link.user_models.'.$userType, User::class);
        $user = $userModel::findOrFail($userId);
        Auth::login($user);

        // Todo
        // mark the thing used

        return back()->with('message', 'Login done!');
        //return redirect()->to('/');
    }

    private function findUserByEmail($email)
    {
        $userModels = config('login-link.user_models', []);

        foreach ($userModels as $key => $model) {
            $user = $model::where('email', $email)->first();
            if ($user) {
                return (object) [
                    'user' => $user,
                    'type' => $key,
                ];
            }
        }

        return null;
    }
}
