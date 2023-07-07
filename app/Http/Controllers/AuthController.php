<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\SendResetPasswordEmail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Devolve a informação relativa ao utilizador autenticado
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(Auth::user())
        ]);
    }

    /**
     * Verifica as credenciais do utilizador e autentica-o através do Laravel Passport
     * @param Request $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
       try {
            // Faz o pedido ao laravel passport
            request()->request->add($this->passportAuthenticationData($request->email, $request->password));
            $request = Request::create(config('auth.PASSPORT_SERVER_URL') . '/oauth/token', 'POST');
            $response = Route::dispatch($request);
            $errorCode = $response->getStatusCode();
            $auth_server_response = json_decode((string) $response->content(), true);
            return response()->json($auth_server_response, $errorCode);
        }
        catch (\Exception $e) {
            return response()->json(['Authentication has failed!', $e->getMessage()], 400);
        }
    }

    /**
     * Remove os tokens de autenticação associados ao utilizador
     * @return JsonResponse
     */
    public function logout(){
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Sessão terminada'
        ]);
    }

    /**
     * Altera a password do utilizador autenticado
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request){
        $user = Auth::user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
           'message' => 'A senha foi alterada'
        ]);
    }

    /**
     * Gera um token que vai ser utilizado para recuperar o acesso à conta
     * Envia um email com um link + token para o utilizador redefinir a sua password
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request){
        $user = User::where('email', $request->email)->first();

        if($user){
            // Gerar token (string random 64 caracteres aleatórios)
            $token = Str::random(64);

            // Adicionar ou atualizar o registo na tabela de password resets, associando o token ao utilizador
            DB::table('password_reset_tokens')->updateOrInsert([
                'email' => $request->email,
            ],
            [
                'token' => $token,
                'created_at' => now(),
            ]);

            // Enviar email com o link que permite fazer a recuperação da conta
            $resetURL = config('app.frontend_url') . 'reset-password?token=' . $token;
            Mail::to($request->email)->send(new SendResetPasswordEmail($resetURL, 'Clique na ligação abaixo para recuperar a sua senha'));
        }

        return response()->json([
           'message' => 'Foi enviado um email de recuperação'
        ]);
    }

    /**
     * Recebe o token de recuperação da conta e a nova password a definir
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request){
        $request->validate([
            'password' => 'required|confirmed|min:8',
            'token' => 'required'
        ]);

        // Verificar se o token é válido
        $resetRequest = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if(!$resetRequest){
            return response()->json([
                'message' => 'O pedido é inválido'
            ], 400);
        }

        // Verificar se o email está associado a um utilizador
        $user = User::where('email', $resetRequest->email)->first();
        if(!$user){
            return response()->json([
                'message' => 'O pedido é inválido'
            ], 400);
        }

        // Atualizar a password do utilizador
        $user->password = Hash::make($request->password);
        $user->save();

        // Descartar token utilizado
        DB::table('password_reset_tokens')
            ->where('token', $request->token)->delete();

        return response()->json([
            'message' => 'A senha foi atualizada'
        ]);
    }

    /**
     * Gera o objeto que vai interagir com o serviço Passport para autenticação
     * @param $username
     * @param $password
     * @return array
     */
    private function passportAuthenticationData($username, $password){
        return [
            'grant_type' => 'password',
            'client_id' => config('auth.CLIENT_ID'),
            'client_secret' => config('auth.CLIENT_SECRET'),
            'username' => $username,
            'password' => $password,
            'scope' => ''
        ];
    }

}
