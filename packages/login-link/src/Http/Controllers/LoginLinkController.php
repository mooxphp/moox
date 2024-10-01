<?php

namespace Moox\LoginLink\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Moox\LoginLink\Models\LoginLink;
use Illuminate\Support\Facades\Config;
use Moox\LoginLink\Mail\LoginLinkEmail;
use Illuminate\Contracts\Encryption\DecryptException;

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

        $this->sendLinkInternal($user);

        return back()->with('message', 'Login link has been sent!');
    }

    public function sendLinkInternal($user)
    {
        $token = Str::random(40);

        $loginLink = LoginLink::create([
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'email' => $user->email,
            'token' => $token,
            'expires_at' => now()->addHours(config('login-link.expiration_time')),
        ]);

        Mail::to($user->email)->send(new LoginLinkEmail($loginLink));
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

        if (isset($loginLink->user_type)) {
            $userType = $loginLink->user_type;
        } else {
            $userType = 'App\Models\User';
        }

        $loginLink->update(['used_at' => now()]);

        $userModel = Config::get('login-link.user_models.' . $userType, User::class);
        $user = $userModel::findOrFail($userId);
        Auth::login($user);

        $redirectTo = config('login-link.redirect_to');

        return redirect($redirectTo)->with('message', 'You have been successfully logged in!');
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
