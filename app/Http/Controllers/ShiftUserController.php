<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftUserResource;
use App\Http\Resources\UserResource;
use App\Models\ShiftUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $user = Auth::user();

        $date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->date)->startOfDay()->format('Y-m-d');

        $shift_user = $user->shiftUsers()
            ->whereHas('schedule', function ($query){
              $query->where('draft', false);
            })
            ->where('date', $date)
            ->first();

        if(!$shift_user)
            return response()->json([
               'data' => null
            ]);

        $available_swaps = ShiftUser::query()
            ->where('schedule_id', $shift_user->schedule_id)
            ->where('date', $date)
            ->where('shift_id', '!=', $shift_user->shift_id)
            ->get();

        $users_without_shift = User::query()
            ->where('service_id', $shift_user->schedule->service_id)
            ->whereDoesntHave('shiftUsers', function($query) use ($date){
                $query->where('date', $date);
            })
            ->get();

        $available_swaps->load([
            'user',
            'shift'
        ]);

        $shift_user->load([
            'user',
            'shift'
        ]);
        return response()->json([
            'user_shift' => new ShiftUserResource($shift_user),
            'available_swaps' => ShiftUserResource::collection($available_swaps),
            'users_without_shift' => UserResource::collection($users_without_shift)
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShiftUser $shift_user)
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
