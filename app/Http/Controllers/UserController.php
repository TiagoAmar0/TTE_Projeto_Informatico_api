<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Mail\SendCredentialsMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt(Str::random()),
            'service_id' => null,
            'type' => $request->type
        ]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now()
        ]);

        $reset_url = config('app.frontend_url') . 'reset-password?token=' . $token;
        Mail::to($request->email)->send(new SendCredentialsMail($reset_url, "Clique na ligação abaixo para aceder à plataforma"));

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

        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $user->delete();

        return response()->json([
            'data' => new UserResource($user)
        ]);
    }


}
