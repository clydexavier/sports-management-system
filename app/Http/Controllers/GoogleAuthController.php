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
                    'name' => $googleUser->name,
                    'password' => Hash::make(Str::random(24)),
                    'google_id' => $googleUser->id,
                    'role' => 'user', // Set default role for new users
                ]
            );
            
            // Update Google ID if not already set
            if (empty($user->google_id)) {
                $user->google_id = $googleUser->id;
                $user->save();
            }
            
            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Redirect to frontend with token
            $redirectUrl = config('app.frontend_url') . '/login?token=' . $token;
            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            // Redirect with error
            $redirectUrl = config('app.frontend_url') . '/login?error=' . urlencode($e->getMessage());
            return redirect($redirectUrl);
        }
    }
}