<?php

namespace Moox\LoginLink\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

        $this->sendLinkInternal($user);

        return back()->with('message', 'Login link has been sent!');
    }

    public function sendLinkInternal($user): void
    {
        $token = Str::random(40);

        $loginLink = LoginLink::create([
            'user_id' => $user->id,
            'user_type' => $user::class,
            'email' => $user->email,
            'token' => $token,
            'expires_at' => now()->addHours(config('login-link.expiration_time')),
        ]);

        Mail::to($user->email)->send(new LoginLinkEmail($loginLink));
    }

    public function authenticate($userId, $token)
    {
        try {
            $userId = urldecode((string) decrypt($userId));
        } catch (DecryptException) {
            return redirect()->route('login')->withErrors(['invalid' => 'Invalid or corrupted link.']);
        }

        $loginLink = LoginLink::where('token', $token)
            ->where('expires_at', '>', now())
            ->where('user_id', $userId)
            ->firstOrFail();

        $userType = $loginLink->user_type ?? User::class;

        $loginLink->update(['used_at' => now()]);

        $userModel = Config::get('login-link.user_models.'.$userType, User::class);
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
