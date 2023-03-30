<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(Auth::user())
        ]);
    }

    /**
     * Logs user in
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciais erradas'], 401);
        }

        // Create a new token for the user
        $user = $request->user();
        $token = $user->createToken('token')->plainTextToken;

        // Return the token as a response
        return response()->json(['token' => $token]);
    }

    public function logout(){
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Sessão terminada'
        ]);
    }
}
