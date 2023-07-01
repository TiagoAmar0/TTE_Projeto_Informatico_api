<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftUserResource;
use App\Models\ShiftUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftUserController extends Controller
{
    /**
     * Lista todas as alocações de turnos do utilizador autenticado num dado dia
     * Utilizado na vista das trocas
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
           'date' => 'required'
        ]);

        $user = Auth::user();

        // Não mostrar turnos do dia atual nem de dias passados
        $date = Carbon::createFromFormat('d-m-Y', $request->date)->startOfDay();
        if($date->isToday() || $date->isPast()){
            return response()->json([
               'message' => 'Este dia já foi concluído'
            ], 422);
        }

        $date = $date->format('Y-m-d');

        // Query que vai buscar todas as alocações em horários publicados da respetiva data
        $shiftUser = $user->shiftUsers()
            ->whereHas('schedule', function ($query){
              $query->where('draft', false);
            })
            ->where('date', $date)
            ->first();

        if(!$shiftUser)
            return response()->json([
               'data' => []
            ]);

        $availableSwaps = [];
        $userIDs = [];

        // Selecionar os turnos alocados a outros enfermeiros do mesmo serviço da respetida data
        // Não seleciona os turnos alocados do utilizador atual
        // Não seleciona as alocações do mesmo turno
        $shiftUsers = ShiftUser::query()
            ->where(function($q) use ($shiftUser, $date){
                $q->where('schedule_id', $shiftUser->schedule_id);
                $q->where('user_id', '!=' ,Auth::id());
                $q->where('shift_id', '!=', $shiftUser->shift_id);
                $q->where('date', $date);
                $q->where('date', ">=", Carbon::now()->startOfDay()->format('Y-m-d'));
            })
            ->whereNotIn('id', $user->swapsUserIsProposing()->pluck('payment_shift_user')->toArray())
            ->with(['user', 'shift'])
            ->get();

        // Criar o array com as possíveis trocas diretas (trocas de turno por turno no mesmo dia)
        foreach ($shiftUsers as $su){
            $availableSwaps[] = [
                'shift_user_id' => $su['id'],
                'user_id' => $su['user_id'],
                'user_name' => $su['user']['name'],
                'date' => $su['date'],
                'day_of_week' => ucfirst(Carbon::createFromFormat('Y-m-d', $su['date'])->shortDayName),
                'shift_id' => $su['shift_id'],
                'shift_name' => $su['shift']['description'],
                'rest' => false
            ];

            $userIDs[$su['user_id']] =  $su['user']['name'];
        }

        // Obter os enfermeiros que estão de folga na data escolhida
        $users_resting_in_date = User::query()
            ->where('service_id', Auth::user()->service->id)
            ->whereNot('id', Auth::id())
            ->whereDoesntHave('shiftUsers', function($query) use ($shiftUser, $date){
                $query->where('schedule_id', $shiftUser->schedule_id);
                $query->where('date', $date);
            })
            ->pluck('id')->toArray();


        // Obter os dias em que os enfermeiro pode pagar a troca
        // Seleciona os dias em que os enfermeiros que folgam na data estão a trabalhar e o enfermeiro autenticado está de folga
        $shiftUsers = ShiftUser::query()
            ->whereIn('user_id', $users_resting_in_date)
            ->where('date', '>', Carbon::now()->startOfDay()->format('Y-m-d'))
            ->whereNotIn('date', ShiftUser::query()->where('user_id', Auth::id())->pluck('date'))
            ->whereNotIn('id', $user->swapsUserIsProposing()->pluck('payment_shift_user')->toArray())
            ->get();


        // Cria o array das trocas 'indiretas' (trocas que requerem pagamento)
        foreach ($shiftUsers as $su){
            $shiftDate = Carbon::createFromFormat('Y-m-d', $su['date']);

            $availableSwaps[] = [
                'shift_user_id' => $su['id'],
                'user_id' => $su['user_id'],
                'user_name' => $su['user']['name'],
                'date' => $su['date'],
                'day_of_week' => ucfirst($shiftDate->shortDayName),
                'day' => $shiftDate->day,
                'month' => $shiftDate->monthName,
                'shift_id' => $su['shift_id'],
                'shift_name' => $su['shift']['description'],
                'rest' => true
            ];

            $userIDs[$su['user_id']] =  $su['user']['name'];
        }

        $shiftUser->load([
            'shift'
        ]);

        return response()->json([
            'user_shift' => new ShiftUserResource($shiftUser),
            'available_swaps' => array_values($availableSwaps) ?: [],
            'user_ids' => $userIDs
        ]);
    }


    /**
     * Devolve o registo de uma alocação de um turno a um utilizador
     */
    public function show(ShiftUser $shiftUser)
    {
        $shiftUser->load([
            'user',
            'shift'
        ]);
        return new ShiftUserResource($shiftUser);
    }
}
