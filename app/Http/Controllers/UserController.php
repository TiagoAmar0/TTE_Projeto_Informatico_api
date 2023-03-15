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
            Auth::user()
        ]);
    }


    /**
     * Logs user in
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return response()->json([
                'status' => 'error',
                'message' => 'The credentials are incorrect.'
            ], 401);

        $user = Auth::user();

        return response()->json([
            'status' => 'success',
            'message' => 'User logged in.',
            'token' => $user->createToken('api')->plainTextToken
        ]);
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
    }
}
