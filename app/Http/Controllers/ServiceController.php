<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index() {
        $services = Service::query()
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json([
            'data' => ServiceResource::collection($services)
        ]);
    }

    public function show(Service $service){
        return response()->json([
            'data' => new ServiceResource($service)
        ]);
    }

    public function destroy(Service $service){
        if($service->users()->exists()){
            return response()->json([
                'message' => 'Não é possível eliminar serviço pois existem enfermeiros associados'
            ], 500);
        }
        $service->delete();

        return response()->json([
            'data' => new ServiceResource($service)
        ]);
    }

    public function associateUser(Service $service, User $user){
        if($user->type == 'admin'){
            return response()->json([
               'message' => 'Um administrador não pode ser associado a um serviço'
            ], 400);
        }

        if($user->type == 'lead-nurse' && $service->users()->where('type', 'lead-nurse')->exists()){
            return response()->json([
                'message' => 'Não é possível associar mais que um enfermeiro chefe a um serviço'
            ], 400);
        }

        $user->service()->associate($service)->save();

        return response()->json([
           'message' => 'O enfermeiro foi associado com sucesso'
        ]);
    }

    public function disassociateUser(Service $service, User $user){
        $user->service()->disassociate()->save();

        return response()->json([
           'message' => 'O enfermeiro foi desassociado do serviço'
        ]);
    }
}
