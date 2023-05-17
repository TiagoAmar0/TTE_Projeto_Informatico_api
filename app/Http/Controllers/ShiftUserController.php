<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftUserResource;
use App\Http\Resources\UserResource;
use App\Models\ShiftUser;
use App\Models\Swap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShiftUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
           'date' => 'required'
        ]);

        $user = Auth::user();

        // Não permitir trocas no próprio dia nem em dias já passados
        $date = Carbon::createFromFormat('d-m-Y', $request->date)->startOfDay();
        if($date->isToday() || $date->isPast()){
            return response()->json([
               'message' => 'Este dia já foi concluído'
            ], 422);
        }

        $date = $date->format('Y-m-d');
        $shift_user = $user->shiftUsers()
            ->whereHas('schedule', function ($query){
              $query->where('draft', false);
            })
            ->where('date', $date)
            ->first();

        if(!$shift_user)
            return response()->json([
               'data' => []
            ]);

        $available_swaps = [];
        $user_ids = [];

        // Selecionar os turnos alocados a outros enfermeiros
        $shift_users = ShiftUser::query()
            ->where(function($q) use ($shift_user, $date){
                $q->where('schedule_id', $shift_user->schedule_id);
                $q->where('user_id', '!=' ,Auth::id());
                $q->where('shift_id', '!=', $shift_user->shift_id);
                $q->where('date', $date);
            })
            ->whereNotIn('id', $user->swapsUserIsProposing()->pluck('payment_shift_user')->toArray())
            ->with(['user', 'shift'])
            ->get();

        // Criar as trocas diretas no mesmo dia
        foreach ($shift_users as $su){
            $available_swaps[] = [
                'shift_user_id' => $su['id'],
                'user_id' => $su['user_id'],
                'user_name' => $su['user']['name'],
                'date' => $su['date'],
                'shift_id' => $su['shift_id'],
                'shift_name' => $su['shift']['description'],
                'rest' => false
            ];

            $user_ids[$su['user_id']] =  $su['user']['name'];
        }

        // Obter os enfermeiros que estão de folga nesse dia
        $users_resting_in_date = User::query()
            ->where('service_id', Auth::user()->service->id)
            ->whereNot('id', Auth::id())
            ->whereDoesntHave('shiftUsers', function($query) use ($shift_user, $date){
                $query->where('schedule_id', $shift_user->schedule_id);
                $query->where('date', $date);
            })
            ->pluck('id')->toArray();


        // Obter dias em que pode pagar a troca da folga
        $shift_users = ShiftUser::query()
            ->whereIn('user_id', $users_resting_in_date)
            ->where('date', '>', Carbon::now()->startOfDay()->format('Y-m-d'))
            ->whereNotIn('date', ShiftUser::query()->where('user_id', Auth::id())->pluck('date'))
            ->whereNotIn('id', $user->swapsUserIsProposing()->pluck('payment_shift_user')->toArray())
            ->get();

        foreach ($shift_users as $su){
            $available_swaps[] = [
                'shift_user_id' => $su['id'],
                'user_id' => $su['user_id'],
                'user_name' => $su['user']['name'],
                'date' => $su['date'],
                'shift_id' => $su['shift_id'],
                'shift_name' => $su['shift']['description'],
                'rest' => true
            ];

            $user_ids[$su['user_id']] =  $su['user']['name'];
        }

        $shift_user->load([
            'shift'
        ]);

        return response()->json([
            'user_shift' => new ShiftUserResource($shift_user),
            'available_swaps' => array_values($available_swaps) ?: [],
            'user_ids' => $user_ids
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(ShiftUser $shift_user)
    {
        $shift_user->load([
            'user',
            'shift'
        ]);
        return new ShiftUserResource($shift_user);
    }
}
