<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\SendCredentialsMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Devolve a lista de todos os utilizadores
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::all());
    }

    /**
     * Adiciona um novo utilizador
     * @param Request $request
     * @return UserResource
     */
    public function store(StoreUserRequest $request){
        // Cria o registo do utilizador
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt(Str::random()),
            'service_id' => null,
            'type' => $request->type
        ]);

        // Gera um token
        $token = Str::random(64);

        // Cria o registo para fazer a definição da password
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now()
        ]);

        // Envia o email para o utilizador de modo a que possa definir a sua password
        $resetURL = config('app.frontend_url') . 'reset-password?token=' . $token;
        Mail::to($request->email)->send(new SendCredentialsMail($resetURL, "Clique na ligação abaixo para aceder à plataforma"));

        return new UserResource($user);
    }

    /**
     * Devolve o registo de um utilizador específico
     * @param User $user
     * @return UserResource
     */
    public function show(User $user){
        return new UserResource($user);
    }

    /**
     * Atualiza os dados de um utilizador
     * @param User $user
     * @param Request $request
     * @return UserResource
     */
    public function update(User $user, UpdateUserRequest $request){
        $user->name = $request->name;
        $user->email = $request->email;
        $user->type = $request->type;
        $user->save();

        return new UserResource($user);
    }

    /**
     * Elimina um utilizador
     * @param User $user
     * @return UserResource|JsonResponse
     */
    public function destroy(User $user){
        // Envia erro caso o utilizador tenha um serviço associado
        if($user->service){
            return response()->json([
                'message' => 'Não é possível apagar um enfermeiro com serviço associado'
            ], 422);
        }

        // Apaga os tokens do utilizador e os seus password resets
        $user->shiftUsers()->delete();
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $user->delete();

        return new UserResource($user);
    }


}
