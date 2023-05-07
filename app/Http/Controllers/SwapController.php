<?php

namespace App\Http\Controllers;

use App\Http\Resources\SwapResource;
use App\Models\ShiftUser;
use App\Models\Swap;
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
        return Auth::user()->swapsProposedToUser;
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
                        'message' => 'Inputs inválidos4',
                    ], 422);

                // Verificar se o enfermeiro a que se pede a troca está de folga
                $exists = ShiftUser::query()
                    ->where('date', $shift_user->date)
                    ->where('user_id', $swap['user_id'])
                    ->exists();

                if($exists)
                    return response()->json([
                        'message' => 'Input inválidos3'
                    ], 422);

                // Verificar se o utilizador a pedir troca tem turnos no dia em que pretende pagar
                $exists = ShiftUser::query()
                    ->where('user_id', Auth::id())
                    ->where('date', $proposed_swap_shift_user->date)
                    ->exists();

                if($exists)
                    return response()->json([
                        'message' => 'Inputs inválidos1',
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
