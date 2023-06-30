<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Http\Resources\UserResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Devolve uma lista de todos os serviços
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $services = Service::query()
            ->orderBy('name', 'ASC')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Devolve o registo de um serviço específico
     * @param Service $service
     * @return ServiceResource
     */
    public function show(Service $service){
        return new ServiceResource($service);
    }

    /**
     * Guarda o registo de um novo serviço
     * @param Request $request
     * @return ServiceResource
     */
    public function store(Request $request){
        $request->validate([
            'name' => ['required', 'unique:services'],
        ]);

        $service = new Service();
        $service->name = $request->name;
        $service->save();

        return new ServiceResource($service);
    }

    /**
     * Atualiza os dados de um serviço
     * @param Service $service
     * @param Request $request
     * @return ServiceResource
     */
    public function update(Service $service, Request $request){
        $request->validate([
            'name' => ['required', 'unique:services,name,'.$service->id],
        ]);

        $service->name = $request->name;
        $service->save();

        return new ServiceResource($service);
    }

    /**
     * Elimina um serviço
     * @param Service $service
     * @return JsonResponse|ServiceResource
     */
    public function destroy(Service $service){
        // Verifica se já existem utilizadores associados ao serviço e manda erro se sim
        if($service->users()->exists()){
            return response()->json([
                'message' => 'Não é possível eliminar serviço pois existem enfermeiros associados'
            ], 500);
        }
        $service->delete();

        return new ServiceResource($service);
    }

    /**
     * Associa um utilizador a um serviço
     * @param Service $service
     * @param User $user
     * @return JsonResponse
     */
    public function associateUserToService(Service $service, User $user){
        // Administradores não podem estar associados a um serviço
        if($user->type == 'admin'){
            return response()->json([
               'message' => 'Um administrador não pode ser associado a um serviço'
            ], 400);
        }

        // Apenas pode existir 1 enfermeiro chefe por serviço
        if($user->type == 'lead-nurse' && $service->users()->where('type', 'lead-nurse')->exists()){
            return response()->json([
                'message' => 'Não é possível associar mais que um enfermeiro chefe a um serviço'
            ], 400);
        }

        $user->service()->associate($service)->save();

        return response()->json([
            'message' => 'O enfermeiro foi associado com sucesso',
            'data' => [
                'user' => new UserResource($user),
                'service' => new ServiceResource($service)
            ]
        ]);
    }

    /**
     * @param Service $service
     * @param User $user
     * @return JsonResponse
     */
    public function disassociateUserToService(Service $service, User $user){
        $user->service()->disassociate()->save();

        return response()->json([
           'message' => 'O enfermeiro foi desassociado do serviço',
            'data' => [
                'user' => new UserResource($user),
                'service' => new ServiceResource($service)
            ]
        ]);
    }
}
