<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftResource;
use App\Models\Service;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
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
        $shifts = $service->shifts()->orderBy('name')->get();

        $intervalsNotCovered = $this->checkDayIntervalsWithoutShifts($shifts);

        return response()->json([
            'intervals' => $intervalsNotCovered,
            'shifts' => ShiftResource::collection($shifts)
        ]);
    }

    public function show(Service $service, Shift $shift){
        return new ShiftResource($shift);
    }

    public function update(Service $service, Shift $shift, Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'nurses_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $start_hour = Carbon::createFromFormat('H:i', $request->start);
        $end_hour = Carbon::createFromFormat('H:i', $request->end);

        // Caso seja um turno que vai de um dia para o outro
        if($start_hour->greaterThan($end_hour))
            $end_hour->addDay();

        $minutes = $start_hour->diffInMinutes($end_hour);
        // Validar se o formato da duração está correto
        if($minutes <= 0){
            return response()->json([
                'message' => 'Duração do turno inválida'
            ], 422);
        }

        $shift->update([
            'name' => $request->name,
            'description' => $request->description,
            'start' => $request->start,
            'end' => $request->end,
            'nurses_qty' => $request->nurses_qty,
            'minutes' => $minutes
        ]);

        $service->shifts()->save($shift);

        return response()->json([
            'message' => 'Turno associado ao serviço'
        ]);
    }

    public function store(Service $service, Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'nurses_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $start_hour = Carbon::createFromFormat('H:i', $request->start);
        $end_hour = Carbon::createFromFormat('H:i', $request->end);

        // Caso seja um turno que vai de um dia para o outro
        if($start_hour->greaterThan($end_hour))
            $end_hour->addDay();

        $minutes = $start_hour->diffInMinutes($end_hour);
        // Validar se o formato da duração está correto
        if($minutes <= 0){
            return response()->json([
                'message' => 'Duração do turno inválida'
            ], 422);
        }

        $shift = new Shift([
           'name' => $request->name,
            'description' => $request->description,
            'start' => $request->start,
            'end' => $request->end,
            'nurses_qty' => $request->nurses_qty,
            'minutes' => $minutes
        ]);

        $service->shifts()->save($shift);

        return response()->json([
            'message' => 'Turno associado ao serviço'
        ]);
    }

    public function destroy(Service $service, Shift $shift){
        if($shift->shiftUsers()->exists()){
            return response()->json([
                'message' => 'Este turno tem horários atribuídos'
            ], 500);
        }

        $shift->delete();

        return response()->json([
           'message' => 'O turno foi eliminado'
        ]);
    }

    private function checkDayIntervalsWithoutShifts($shifts)
    {
        $coveredIntervals = [];

        foreach ($shifts as $shift) {
            $start = Carbon::createFromFormat('H:i:s', $shift['start'])->startOfMinute();
            $end = Carbon::createFromFormat('H:i:s', $shift['end'])->addMinute()->startOfMinute();

            $coveredIntervals[] = [$start, $end];
        }

        $startOfDay = Carbon::createFromTime(0, 0);
        $endOfDay = Carbon::createFromTime(23, 59);

        $intervalsNotCovered = [];

        usort($coveredIntervals, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        $lastEnd = $startOfDay;
        foreach ($coveredIntervals as $interval) {
            $start = $interval[0];
            $end = $interval[1];

            if ($start->greaterThan($lastEnd)) {
                $intervalsNotCovered[] = [$lastEnd, $start];
            }

            $lastEnd = max($lastEnd, $end);
        }

        if ($lastEnd->lessThan($endOfDay)) {
            $intervalsNotCovered[] = [$lastEnd, $endOfDay];
        }

        return array_map(function ($interval) {
            return [
                $interval[0]->format('H:i'),
                $interval[1]->format('H:i'),
            ];
        }, $intervalsNotCovered);
    }
}
