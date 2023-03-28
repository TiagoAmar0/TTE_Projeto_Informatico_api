<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
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
}
