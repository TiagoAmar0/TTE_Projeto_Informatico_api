<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Mail\SendResetPasswordEmail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais erradas'], 401);
        }

        // Create a new token for the user
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

    public function changePassword(Request $request){
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed'
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
           'message' => 'A senha foi alterada'
        ]);
    }

    public function forgotPassword(Request $request){
        $request->validate([
            'email' => 'required|email|unique:password_reset_tokens,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if($user){
            $token = Str::random(64);
            DB::table('password_reset_tokens')->insert([
                'token' => $token,
                'created_at' => now(),
                'email' => $request->email,
            ]);

            $reset_url = config('app.frontend_url') . 'reset-password/token?=' . $token;
            Mail::to($request->email)->send(new SendResetPasswordEmail($reset_url, 'Clique na ligação abaixo para recuperar a sua senha'));
        }

        return response()->json([
           'message' => 'Foi enviado um email de recuperação'
        ]);
    }

    public function resetPassword(Request $request){
        $request->validate([
            'password' => 'required|confirmed|min:8',
            'token' => 'required'
        ]);

        $reset_request = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if(!$reset_request){
            return response()->json([
                'message' => 'O pedido é inválido'
            ], 400);
        }

        $user = User::where('email', $reset_request->email)->first();
        if(!$user){
            return response()->json([
                'message' => 'O pedido é inválido'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        DB::table('password_reset_tokens')
            ->where('token', $request->token)->delete();

        return response()->json([
            'message' => 'A senha foi atualizada'
        ]);
    }
}
