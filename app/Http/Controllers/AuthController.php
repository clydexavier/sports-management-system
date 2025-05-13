<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\AuthRequests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AuthRequests\RegisterRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request) {
        $data = $request->validated();
    
        if(!Auth::attempt($data)){
            return response()->json([
                'message' => 'Invalid login, please try again.'
            ], 422);
        }
        
        $user = Auth::user();
        
        // Check if the user role is 'user' - this block updated for consistency
        if($user->role === 'user'){
            // First logout the user
            Auth::logout();
            
            // For Laravel Sanctum, revoke any existing tokens
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            
            // Return the message with proper status code
            return response()->json([
                'pending' => true, // Added this flag for consistency with Google flow
                'message' => 'Please wait for the administrator to assign your appropriate role. You will be notified once your account is approved.'
            ], 403);
        }
        
        // Only create token if user is not a basic 'user' role
        $token = $user->createToken('main')->plainTextToken;
    
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => 'user',
        ]);
        
        // No token for users with 'user' role - updated to match Google flow
        if($user->role === 'user'){
            return response()->json([
                'pending' => true, 
                'message' => 'Your account has been created successfully. Please wait for administrator approval before logging in.'
            ], 201);
        }
        
        // Only create and return token for users with other roles
        $token = $user->createToken('main')->plainTextToken;
    
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response('',204);
    }
}