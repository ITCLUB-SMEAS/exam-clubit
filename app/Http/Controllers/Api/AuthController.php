<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Timing attack prevention
        $dummyHash = '$2y$12$K4o0hLJLfLKJfLKJfLKJfOK4o0hLJLfLKJfLKJfLKJfOK4o0hLJLf';
        $passwordToCheck = $user ? $user->password : $dummyHash;
        $validPassword = Hash::check($request->password, $passwordToCheck) && $user;

        if (!$validPassword) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        // Revoke old tokens (keep only last 5)
        $user->tokens()->orderBy('created_at', 'desc')->skip(5)->take(100)->delete();

        // Create token with expiration (24 hours)
        $token = $user->createToken('api-token', ['*'], Carbon::now()->addHours(24))->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'expires_at' => Carbon::now()->addHours(24)->toISOString(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }
}
