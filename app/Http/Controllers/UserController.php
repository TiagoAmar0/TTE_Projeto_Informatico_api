<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Mail\SendCredentialsMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
           'data' => UserResource::collection(User::all())
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:App\Models\User,email',
            'type' => ['required','string', Rule::in(['nurse', 'lead-nurse', 'admin'])]
        ]);

        $random_password = Str::random();

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($random_password),
            'service_id' => null,
            'type' => $request->type
        ]);

        // Send email to user containing credentials
        Mail::to($user->email)->send(new SendCredentialsMail($user->email, $random_password));

        return response()->json([
            'data' => new UserResource($user)
        ]);
    }

    public function show(User $user){
        return response()->json([
            'data' => new UserResource($user)
        ]);
    }

    public function update(User $user, Request $request){
        $request->validate([
            'name' => 'required|string',
            'email' => ['required', 'email', 'unique:App\Models\User,email,'.$user->id],
            'type' => ['required','string', Rule::in(['nurse', 'lead-nurse', 'admin'])]
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->type = $request->type;
        $user->save();

        return response()->json([
            'data' => new UserResource($user)
        ]);
    }

    public function destroy(User $user){
        if($user->service){
            return response()->json([
                'message' => 'Não é possível apagar um enfermeiro com serviço associado'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'data' => new UserResource($user)
        ]);
    }


}
