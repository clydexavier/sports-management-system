<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        $redirectUrl = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        
        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find existing user or create new one
            $user = User::firstOrCreate(
                ['email' => $googleUser->email],
                [
                    'name'       => $googleUser->name,
                    'password'   => Hash::make(Str::random(24)),
                    'google_id'  => $googleUser->id,
                    'avatar_url' => $googleUser->avatar, // Store avatar from Google
                    'role'       => 'user', // Default role
                ]
            );

            // Update Google ID and avatar URL if not already set or changed
            $updated = false;
            if (empty($user->google_id)) {
                $user->google_id = $googleUser->id;
                $updated = true;
            }

            if (empty($user->avatar_url) || $user->avatar_url !== $googleUser->avatar) {
                $user->avatar_url = $googleUser->avatar;
                $updated = true;
            }

            if ($updated) {
                $user->save();
            }

            // Check if the user has 'user' role
            if ($user->role === 'user') {
                // Redirect to frontend with a message that they need to wait for approval
                $redirectUrl = config('app.frontend_url') . '/login?pending=true&message=' . urlencode('Please wait for the administrator to assign your appropriate role. You will be notified once your account is approved.');
                return redirect($redirectUrl);
            }

            // Only create token if user doesn't have the 'user' role
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to frontend with token
            $redirectUrl = config('app.frontend_url') . '/login?token=' . $token;
            return redirect($redirectUrl);

        } catch (\Exception $e) {
            $redirectUrl = config('app.frontend_url') . '/login?error=' . urlencode($e->getMessage());
            return redirect($redirectUrl);
        }
    }

}