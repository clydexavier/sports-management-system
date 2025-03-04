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
        ]);

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