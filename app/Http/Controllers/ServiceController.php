<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Http\Resources\UserResource;
use App\Models\Service;
use App\Models\User;
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

    public function store(Request $request){
        $request->validate([
            'name' => ['required', 'unique:services'],
            'nurses_qty_first_shift' => ['required', 'min:1', 'numeric'],
            'nurses_qty_second_shift' => ['required', 'min:1', 'numeric'],
            'nurses_qty_third_shift' => ['required', 'min:1', 'numeric'],
        ]);

        $service = new Service();
        $service->name = $request->name;
        $this->nurses_qty_first_shift = $request->nurses_qty_first_shift;
        $this->nurses_qty_second_shift = $request->nurses_qty_second_shift;
        $this->nurses_qty_third_shift = $request->nurses_qty_third_shift;
        $service->save();

        return response()->json([
           'data' => new ServiceResource($service)
        ]);
    }

    public function update(Service $service, Request $request){
        $request->validate([
            'name' => ['required', 'unique:services,name,'.$service->id],
            'nurses_qty_first_shift' => ['required', 'min:1', 'numeric'],
            'nurses_qty_second_shift' => ['required', 'min:1', 'numeric'],
            'nurses_qty_third_shift' => ['required', 'min:1', 'numeric'],
        ]);

        $service->name = $request->name;
        $this->nurses_qty_first_shift = $request->nurses_qty_first_shift;
        $this->nurses_qty_second_shift = $request->nurses_qty_second_shift;
        $this->nurses_qty_third_shift = $request->nurses_qty_third_shift;
        $service->save();

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

    public function associateUserToService(Service $service, User $user){
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
            'message' => 'O enfermeiro foi associado com sucesso',
            'data' => [
                'user' => new UserResource($user),
                'service' => new ServiceResource($service)
            ]
        ]);
    }

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
