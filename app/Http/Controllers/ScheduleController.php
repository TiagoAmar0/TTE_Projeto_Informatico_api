<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ShiftUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Service $service)
    {
        return ScheduleResource::collection($service->schedules()->get());
    }

    /**
     * Store a newly created resource in storage.
     * @throws \Throwable
     */
    public function store(Service $service, Request $request)
    {
        $request->validate([
            'draft' => ['required', 'boolean'],
            'data' => ['required', 'array'],
            'date_range' => ['required', 'array'],
            'date_range.*' => ['date'],
            'data.*.nurses_total' => ['required', 'numeric'],
            'data.*.date' => ['required'],
            'data.*.date_formatted' => ['required'],
        ]);

        if($request->draft === false){
            // Verify if already exists a schedule in date range
            $exists = Schedule::query()
                ->whereBetween('start', [$request->date_range[0], $request->date_range[1]])
                ->orWhereBetween('end', [$request->date_range[0], $request->date_range[1]])
                ->exists();

            if($exists){
                return response()->json([
                    'message' => 'Já existem horários definidos neste intervalo de tempo'
                ], 422);
            }
        }

        // Verify if for each date, there are not duplicated user or date entries
        $days = array_map(function($day){
            return $day['date'];
        }, $request->data);

        if(count($days) !== count(array_unique($days))){
            return response()->json([
                'message' => 'Dados inválidos. (Datas duplicadas)'
            ], 422);
        }

        $service_users = $service->users()->pluck('id')->toArray();
        foreach ($request->data as $day) {
            foreach ($day['nurses'] as $nurse){
                if(!in_array($nurse['id'], $service_users)){
                    return response()->json([
                        'message' => 'Dados inválidos. (Utilizadores inválidos)'
                    ], 422);
                }
            }
        }

        try {
            $schedule = new Schedule();
//            return response()->json([
//               'start' => $request->date_range[0],
//               'end' => $request->date_range[1]
//            ]);
            $schedule->start = $request->date_range[0];
            $schedule->end = $request->date_range[1];
            $schedule->service_id = $service->id;
            $schedule->draft = $request->draft;
            $schedule->saveOrFail();

            $data = [];
            foreach ($request->data as $day){
                foreach ($day['nurses'] as $nurse){
                    if($nurse['shift'] && $nurse['id']){
                        $data[] = [
                            'user_id' => $nurse['id'],
                            'shift_id' => $nurse['shift'],
                            'schedule_id' => $schedule->id,
                            'date' => $day['date_formatted']
                        ];
                    }
                }
            }

            ShiftUser::query()->insert($data);

        } catch (\Throwable $t){
            if(isset($schedule) && $schedule->wasRecentlyCreated){
                $schedule->delete();
            }
            return response()->json([
                'message' => 'Erro ao processar pedido. Tente de novo',
                'ex' => $t->getMessage()
            ], 500);
        }




        return response()->json([
            'message' => $request['draft'] ? 'Rascunho guardado' : 'Horário criado com sucesso',
            'data' => new ScheduleResource($schedule)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service, Schedule $schedule)
    {
        $schedule->load([
            'userShifts',
            'service'
        ]);
        return new ScheduleResource($schedule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Service $service, Schedule $schedule, Request $request)
    {

        $request->validate([
            'draft' => ['required', 'boolean'],
            'data' => ['required', 'array'],
            'date_range' => ['required', 'array'],
            'date_range.*' => ['date'],
            'data.*.nurses_total' => ['required', 'numeric'],
            'data.*.date' => ['required'],
            'data.*.date_formatted' => ['required'],
        ]);

        if($request->draft === false){
            // Verify if already exists a schedule in date range
            $exists = Schedule::query()
                ->whereNot('id', $schedule->id)
                ->where(function($query) use ($request){
                    $query->whereBetween('start', [$request->date_range[0], $request->date_range[1]]);
                    $query->orWhereBetween('end', [$request->date_range[0], $request->date_range[1]]);
                })

                ->exists();

            if($exists){
                return response()->json([
                    'message' => 'Já existem horários definidos neste intervalo de tempo'
                ], 422);
            }
        }

        // Verify if for each date, there are not duplicated user or date entries
        $days = array_map(function($day){
            return $day['date'];
        }, $request->data);

        if(count($days) !== count(array_unique($days))){
            return response()->json([
                'message' => 'Dados inválidos. (Datas duplicadas)'
            ], 422);
        }

        $service_users = $service->users()->pluck('id')->toArray();
        foreach ($request->data as $day) {
            foreach ($day['nurses'] as $nurse){
                if(!in_array($nurse['id'], $service_users)){
                    return response()->json([
                        'message' => 'Dados inválidos. (Utilizadores inválidos)'
                    ], 422);
                }
            }
        }

        try {
            $schedule->start = $request->date_range[0];
            $schedule->end = $request->date_range[1];
            $schedule->draft = $request->draft;
            $schedule->saveOrFail();

            foreach ($request->data as $day) {
                foreach ($day['nurses'] as $nurse) {
                    if ($nurse['shift'] && $nurse['id']) {
                        $schedule->userShifts()->updateOrCreate([
                            'user_id' => $nurse['id'],
                            'schedule_id' => $schedule->id,
                            'date' => $day['date_formatted']
                        ],
                        [
                            'shift_id' => $nurse['shift'],
                        ]);
                    } else {
                        $schedule->userShifts()
                            ->where('user_id', $nurse['id'])
                            ->where('date', $day['date_formatted'])
                            ->delete();
                    }
                }
            }
        } catch (\Throwable $t){
            return response()->json([
                'message' => 'Erro ao processar pedido. Tente de novo',
                'ex' => $t->getMessage()
            ], 500);
        }


        // Apagar todos os shift_users fora do range associado ao service
        $teste = $schedule->userShifts()
            ->where('date', '<', $schedule->start)
            ->orWhere('date', '>', $schedule->end)
            ->get();

        return response()->json([
            'message' => 'O horário foi atualizado'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service, Schedule $schedule)
    {
        $schedule->userShifts()->delete();
        $schedule->delete();
        return response()->json([
            'message' => 'O horário foi eliminado'
        ]);
    }
}
