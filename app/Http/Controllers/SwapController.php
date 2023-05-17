<?php

namespace App\Http\Controllers;

use App\Http\Resources\SwapResource;
use App\Models\ShiftUser;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    public function swapsUserIsProposing(){
        return SwapResource::collection(Auth::user()->swapsUserIsProposing()->get());
    }

    public function swapsProposedToUser(){
        return SwapResource::collection(Auth::user()->swapsProposedToUser()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_shift.id' => ['required', 'exists:App\Models\ShiftUser,id'],
            'swaps' => ['required','array'],
            'swaps.*.user_id' => ['required', 'exists:App\Models\User,id'],
            'swaps.*.date' => ['required', 'date'],
            'swaps.*.shift_user_id' => ['required', 'exists:App\Models\ShiftUser,id'],
            'swaps.*.rest' => ['required', 'boolean']
        ]);

        $shift_user = ShiftUser::findOrFail($request->user_shift['id']);

        $data = [];
        foreach ($request->swaps as $swap){
            $proposed_swap_shift_user = ShiftUser::findOrFail($swap['shift_user_id']);

            // Verificações de integridade
            if($swap['rest']){
                // Verificar se as datas batem certo
                if($proposed_swap_shift_user->date === $shift_user->date)
                    return response()->json([
                        'message' => 'Inputs inválidos',
                    ], 422);

                // Verificar se o enfermeiro a que se pede a troca está de folga
                $exists = ShiftUser::query()
                    ->where('date', $shift_user->date)
                    ->where('user_id', $swap['user_id'])
                    ->exists();

                if($exists)
                    return response()->json([
                        'message' => 'Input inválidos'
                    ], 422);

                // Verificar se o utilizador a pedir troca tem turnos no dia em que pretende pagar
                $exists = ShiftUser::query()
                    ->where('user_id', Auth::id())
                    ->where('date', $proposed_swap_shift_user->date)
                    ->exists();

                if($exists)
                    return response()->json([
                        'message' => 'Inputs inválidos',
                        'date' => $proposed_swap_shift_user->date
                    ], 422);


                $data[] = [
                    'proposing_user_id' => Auth::id(),
                    'target_user_id' => $swap['user_id'],
                    'target_shift_user' => $shift_user->id,
                    'payment_shift_user' => $swap['shift_user_id'],
                    'direct' => false,
                ];

            } else {
                // Verificações de integridade

                // Verificar se a data do turno a propor é igual ao atual
                if($proposed_swap_shift_user->date !== $shift_user->date)
                    return response()->json([
                        'message' => 'Inputs inválidos2'
                    ], 422);

                $data[] = [
                    'proposing_user_id' => Auth::id(),
                    'target_user_id' => $swap['user_id'],
                    'target_shift_user' => $shift_user->id,
                    'payment_shift_user' => $proposed_swap_shift_user->id,
                    'direct' => true,
                ];
            }
        }

        Swap::query()->insert($data);

        return response()->json([
            'message' => (count($request->swaps) ? 'Pedidos' : 'Pedido') . ' de troca submetidos',
            'swaps' => Swap::all()
        ]);
    }

    /**
     * Aprovar troca de turno
     * @param Swap $swap
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveSwap(Swap $swap){
        $target_shift_user = ShiftUser::query()->findOrFail($swap->target_shift_user);
        $payment_shift_user = ShiftUser::query()->findOrFail($swap->payment_shift_user);

        // Trocar diretamente os turnos
        $target_shift_user->update([
            'user_id' => $swap->target_user_id
        ]);

        $payment_shift_user->update([
            'user_id' => $swap->proposing_user_id
        ]);

        $swap->update([
            'status' => 'accepted'
        ]);

        // Apagar todas as trocas ainda abertas para aquele turno
        Swap::query()
            ->where('target_shift_user', $swap->target_shift_user)
            ->whereNot('id', $swap->id)
            ->where('status', 'pending')
            ->delete();

        // TODO: enviar email a confirmar a aprovação do pedido

        return response()->json([
            'message' => 'A sua troca foi aprovada',
        ]);
    }

    public function rejectSwap(Swap $swap){
        $swap->update([
            'status' => 'rejected'
        ]);

        $user = User::query()->findOrFail($swap->proposing_user_id);
        if(!$user->swapsUserIsProposing()->where('target_shift_user', $swap->target_shift_user)->whereNot('status', 'rejected')->count()){
            // TODO: enviar email caso todas as propostas de troca daquele turno tenham sido rejeitadas
        }

        return response()->json([
           'message' => 'Resposta enviada'
        ]);
    }
}
