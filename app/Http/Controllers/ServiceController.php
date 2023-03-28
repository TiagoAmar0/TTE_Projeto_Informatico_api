<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request) {
        $query = Service::query();

        if($request->search){
            $query->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($request->search) . '%']);
        }

       $services = $query->orderBy('name', 'ASC')->paginate(10);

        return response()->json($services);
    }
}
