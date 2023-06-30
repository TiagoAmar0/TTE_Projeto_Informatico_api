<?php

namespace App\Http\Controllers;

use App\Http\Resources\SwapResource;
use App\Mail\SendResetPasswordEmail;
use App\Mail\SendSwapNotificationEmail;
use App\Models\ShiftUser;
use App\Models\Swap;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SwapController extends Controller
{

    /**
     * Devolve a lista de todas as trocas que o utilizador autenticado está a propor
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function swapsUserIsProposing(){
        return SwapResource::collection(Auth::user()->swapsUserIsProposing()->get());
    }

    /**
     * Devolve a lista de todas as trocas que estão a ser propostas ao utilizador autenticado
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function swapsProposedToUser(){
        return SwapResource::collection(Auth::user()->swapsProposedToUser()->get());
    }

    /**
     * Cria um pedido de troca por parte do utilizador autenticado
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

        $shiftUser = ShiftUser::findOrFail($request->user_shift['id']);

        $data = [];
        foreach ($request->swaps as $swap){
            $proposedSwapShiftUser = ShiftUser::findOrFail($swap['shift_user_id']);

            // Caso seja uma troca indireta (turno por folga)
            if($swap['rest']){
                if($proposedSwapShiftUser->date === $shiftUser->date)
                    return response()->json([
                        'message' => 'Inputs inválidos',
                    ], 422);

                // Verificar se o enfermeiro a quem se propõe a troca está de folga
                $exists = ShiftUser::query()
                    ->where('date', $shiftUser->date)
                    ->where('user_id', $swap['user_id'])
                    ->exists();

                // Se existir um turno alocado a esse utilizador, envia erro
                if($exists)
                    return response()->json([
                        'message' => 'Input inválidos'
                    ], 422);

                // Verificar se o utilizador a pedir troca tem turnos no dia em que pretende pagar
                // Se sim, enviar erro
                $exists = ShiftUser::query()
                    ->where('user_id', Auth::id())
                    ->where('date', $proposedSwapShiftUser->date)
                    ->exists();

                if($exists)
                    return response()->json([
                        'message' => 'Inputs inválidos',
                        'date' => $proposedSwapShiftUser->date
                    ], 422);


                $data[] = [
                    'proposing_user_id' => Auth::id(),
                    'target_user_id' => $swap['user_id'],
                    'target_shift_user' => $shiftUser->id,
                    'payment_shift_user' => $swap['shift_user_id'],
                    'direct' => false,
                ];

            } else {
                // Verificar se a data do turno a propor é igual ao atual
                if($proposedSwapShiftUser->date !== $shiftUser->date)
                    return response()->json([
                        'message' => 'Inputs inválidos'
                    ], 422);

                $data[] = [
                    'proposing_user_id' => Auth::id(),
                    'target_user_id' => $swap['user_id'],
                    'target_shift_user' => $shiftUser->id,
                    'payment_shift_user' => $proposedSwapShiftUser->id,
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
     * Aprova um pedido de troca
     * @param Swap $swap
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveSwap(Swap $swap){
        $targetShiftUser = ShiftUser::query()->findOrFail($swap->target_shift_user);
        $paymentShiftUser = ShiftUser::query()->findOrFail($swap->payment_shift_user);

        // Trocar os turnos
        $targetShiftUser->update([
            'user_id' => $swap->target_user_id
        ]);

        $paymentShiftUser->update([
            'user_id' => $swap->proposing_user_id
        ]);

        // Mudar o estado da troca para aceite
        $swap->update([
            'status' => 'accepted'
        ]);

        // Apagar todas as trocas ainda abertas para aquele turno
        Swap::query()
            ->where('target_shift_user', $swap->target_shift_user)
            ->whereNot('id', $swap->id)
            ->where('status', 'pending')
            ->delete();

        $proposingUser = $swap->proposingUser;
        $targetUser = $swap->targetUser;

        $date = $swap->targetShiftUser->date;


        // Enviar notificação email a informar que a troca foi aceite
        $message = "$targetUser->name aceitou a sua proposta de troca para dia $date";
        $subject = "Troca aceite";

        Mail::to($proposingUser->email)->send(new SendSwapNotificationEmail($message, $subject));

        return response()->json([
            'message' => 'A sua troca foi aprovada',
        ]);
    }

    /**
     * Rejeita um pedido de troca
     * @param Swap $swap
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectSwap(Swap $swap){
        // Atualiza a troca para rejeitada
        $swap->update([
            'status' => 'rejected'
        ]);

        $user = $swap->proposingUser;

        // Caso todos os pedidos de troca do utilizador para um dado turno forem rejeitos, envia notificação email a notificar
        if(!$user->swapsUserIsProposing()->where('target_shift_user', $swap->target_shift_user)->whereNot('status', 'rejected')->count()){
            $date = $swap->targetShiftUser->date;
            $shift = $swap->targetShiftUser->shift->description;

            $message = "Todas as trocas propostas para o turno $shift de $date foram rejeitadas";
            $subject = "Propostas rejeitadas para $date";
            Mail::to($user->email)->send(new SendSwapNotificationEmail($message, $subject));
        }

        return response()->json([
           'message' => 'Resposta enviada'
        ]);
    }

    /**
     * Devolve o histórico de todas as trocas efetuadas pelo utilizador autenticado
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function swapsHistory(){

        $acceptedSwaps = Swap::query()
            ->where('status', 'accepted')
            ->where(function($query) {
                $query->where('target_user_id', Auth::id());
                $query->orWhere('proposing_user_id', Auth::id());
            })
            ->get();

        return SwapResource::collection($acceptedSwaps);
    }
}
