<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request) {
        $data = $request->validate([
            'email' => ['required', 'min:3', 'max:30'],
            'password' => ['required', 'min:8', 'max:100']
        ]);

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
        $data = $request->validate([
            'name' => 'required',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed','min:8', 'max:100'],
            'password_confirmation' => ['required', 'min:8', 'max:100']

        ]);

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