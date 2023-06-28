<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftResource;
use App\Models\Service;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function associateNurseToShift(Shift $shift, User $user, Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        if($user->shifts()->wherePivot('date', $request->date)->exists()){
            return response()->json([
                'message' => 'O utilizador já tem um turno associado nesse dia'
            ], 422);
        }

        if(!$user->service || !$user->service->id != $shift->service->id){
            return response()->json([
                'message' => 'O utilizador não pertence ao serviço do turno'
            ], 422);
        }

        $user->shifts()->attach($shift, ['date' => $request->date]);

        return response()->json([
            'message' => 'Turno associado ao enfermeiro'
        ]);
    }

    public function disassociateNurseToShift(Shift $shift, User $user, Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $user->shifts()->wherePivot('date', $request->date)->wherePivot('shift_id', $shift->id)->delete();

        return response()->json([
            'message' => 'Turno removido ao enfermeiro'
        ]);
    }

    public function index(Service $service){
        return ShiftResource::collection($service->shifts);
    }

    public function store(Service $service, Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'nurses_qty' => ['required', 'numeric', 'min:0'],
            'hours' => ['hours' => ['required', 'regex:/^\d{2}:\d{2}$/']],
        ]);

        // Converter horas para minutos
        list($hours, $minutes) = explode(':', $request->hours);

        $hours = (int) $hours;
        $minutes = (int) $minutes;

        // Validar se o formato da duração está correto
        if(($hours <= 0 && $minutes == 0) || $hours < 0 || $minutes < 0 || $minutes > 59){
            return response()->json([
                'message' => 'Duração do turno inválida'
            ], 422);
        }

        $totalMinutes = ($hours * 60) + $minutes;

        $shift = new Shift([
           'name' => $request->name,
            'description' => $request->description,
            'start' => $request->start,
            'end' => $request->end,
            'nurses_qty' => $request->nurses_qty,
            'minutes' => $totalMinutes
        ]);

        $service->shifts()->save($shift);

        return response()->json([
            'message' => 'Turno associado ao serviço'
        ]);
    }
}
