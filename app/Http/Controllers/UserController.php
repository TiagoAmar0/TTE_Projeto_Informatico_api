<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
           'data' => User::all()
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => Auth::user()
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

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:App\Models\User,email',
            'password' => 'required|string',
            'sector_id' => 'required|exists:App\Models\Sector,id',
            'role' => ['required','string', Rule::in(['nurse', 'lead-nurse', 'super-admin'])]
        ]);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'sector_id' => $request->sector_id,
        ]);

        // Add role
//        $user->assignRole($request->role);
    }

    public function logout(){
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Sessão terminada'
        ]);
    }
}
