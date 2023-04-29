<?php

namespace App\Http\Controllers;

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
}
