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
        
        // Check if the user role is 'user' and log them out if it is
        if($user->role === 'user'){
            Auth::logout();
            
            // For Laravel Sanctum, we should revoke any existing tokens
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            
            return response()->json([
                'message' => 'Please wait for the administrator to assign your appropriate role. You will be notified once your account is approved.'
            ], 403);
        }
        
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
            'role' => 'user', // Setting default role to 'user' for new registrations
        ]);
        
        // No token for users with 'user' role
        if($user->role === 'user'){
            return response()->json([
                'message' => 'Your account has been created successfully. Please wait for administrator approval before logging in.'
            ], 201);
        }
        
        // Only create and return token for users with other roles (e.g., admin, editor, etc.)
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