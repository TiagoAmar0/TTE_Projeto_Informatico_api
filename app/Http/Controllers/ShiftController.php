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
    /**
     * Lista todos os turnos de um serviço
     * @param Service $service
     * @return JsonResponse
     */
    public function index(Service $service){
        $shifts = $service->shifts()->orderBy('name')->get();

        // Calcula quais os momentos de um dia que não estão cobertos por um turno
        $intervalsNotCovered = $this->checkDayIntervalsWithoutShifts($shifts);

        return response()->json([
            'intervals' => $intervalsNotCovered,
            'shifts' => ShiftResource::collection($shifts)
        ]);
    }

    /**
     * Devolve o registo de um dado turno
     * @param Service $service
     * @param Shift $shift
     * @return ShiftResource
     */
    public function show(Service $service, Shift $shift){
        return new ShiftResource($shift);
    }

    /**
     * Atualiza os dados de um turno de um serviço
     * @param Service $service
     * @param Shift $shift
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Service $service, Shift $shift, Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'nurses_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $startHour = Carbon::createFromFormat('H:i', $request->start);
        $endHour = Carbon::createFromFormat('H:i', $request->end);

        // Caso seja um turno que vai de um dia para o outro, adiciona um dia para calcular o total de minutos do turno
        if($startHour->greaterThan($endHour))
            $endHour->addDay();

        // Calcular total de minutos do turno
        $minutes = $startHour->diffInMinutes($endHour);

        // Validar se o turno tem duração ≥ 0
        if($minutes <= 0){
            return response()->json([
                'message' => 'Duração do turno inválida'
            ], 422);
        }

        // Atualizar turno
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

    /**
     * Guarda um novo turno de um serviço
     * @param Service $service
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Service $service, Request $request){
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'nurses_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $startHour = Carbon::createFromFormat('H:i', $request->start);
        $endHour = Carbon::createFromFormat('H:i', $request->end);

        // Caso seja um turno que vai de um dia para o outro, adiciona um dia para calcular o total de minutos
        if($startHour->greaterThan($endHour))
            $endHour->addDay();

        $minutes = $startHour->diffInMinutes($endHour);
        // Enviar erro caso a duração do turno seja ≤ 0
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

    /**
     * Eliminar os dados de um turno
     * @param Service $service
     * @param Shift $shift
     * @return JsonResponse
     */
    public function destroy(Service $service, Shift $shift){
        // Enviar erro caso já existam registos associados ao turno
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

    /**
     * Calcula os intervalos de um dia que não estão cobertos por um turno
     * @param $shifts
     * @return array
     */
    private function checkDayIntervalsWithoutShifts($shifts): array
    {
        $coveredIntervals = [];

        // Cria a matriz com os intervalos cobertos pelos turnos definidos
        foreach ($shifts as $shift) {
            $start = Carbon::createFromFormat('H:i:s', $shift['start'])->startOfMinute();
            $end = Carbon::createFromFormat('H:i:s', $shift['end'])->addMinute()->startOfMinute();

            $coveredIntervals[] = [$start, $end];
        }

        $startOfDay = Carbon::createFromTime(0, 0);
        $endOfDay = Carbon::createFromTime(23, 59);

        $intervalsNotCovered = [];

        // Ordena os intervalos cobertos por ordem
        usort($coveredIntervals, function ($x, $y) {
            return $x[0] <=> $y[0];
        });


        // Itera os intervalos cobertos e valida com o intervalo que vai do início ao fim do dia
        $lastEnd = $startOfDay;
        foreach ($coveredIntervals as $interval) {
            $start = $interval[0];
            $end = $interval[1];

            // Adiciona intervalo descoberto aos intervalos não cobertos
            if ($start->greaterThan($lastEnd)) {
                $intervalsNotCovered[] = [$lastEnd, $start];
            }

            $lastEnd = max($lastEnd, $end);
        }

        // Se o tempo mais tarde do dia não corresponder ao final do dia, adiciona o intervalo dessa hora até ao fim do dia
        if ($lastEnd->lessThan($endOfDay)) {
            $intervalsNotCovered[] = [$lastEnd, $endOfDay];
        }

        // Mapeia os intervalos não cobertos para uma matriz com as horas em formato hora:minuto
        return array_map(function ($interval) {
            return [
                $interval[0]->format('H:i'),
                $interval[1]->format('H:i'),
            ];
        }, $intervalsNotCovered);
    }
}
