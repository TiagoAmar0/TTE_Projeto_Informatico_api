<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Shift;
use App\Models\ShiftUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psy\Exception\ThrowUpException;

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
    public function store_old(Service $service, Request $request)
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
                            'date' => $day['date']
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

    public function store(Service $service, Request $request)
    {
        $this->validateStoreRequest($request, $service);
        $schedule = $this->createNewSchedule($service, $request);
        $inserted = $this->createShiftUsers($schedule, $request);
        if(!$inserted && !$request['draft']) {
            $schedule->delete();
            return response()->json([
               'message' => 'Não pode submeter um horário sem registos'
            ], 422);
        }
        return response()->json([
            'message' => $request['draft'] ? 'Rascunho guardado' : 'Horário criado com sucesso',
            'data' => new ScheduleResource($schedule)
        ]);
    }


    private function validateStoreRequest(Request $request, Service $service){
        $request->validate([
            'draft' => ['required', 'boolean'],
            'data' => ['required', 'array'],
            'date_range' => ['required', 'array'],
            'date_range.*' => ['date'],
            'data.*.nurses_total' => ['required', 'numeric'],
            'data.*.date' => ['required'],
            'data.*.date_formatted' => ['required'],
        ]);

        if (!$request->draft) {
            $this->verifyIfPublishedScheduleExists($request, $service);
        }

        $this->verifyIfRequestDatesAreUnique($request);

        $this->verifyIfUsersAreValid($request, $service);
    }

    private function verifyIfPublishedScheduleExists(Request $request, Service $service){
        $exists = Schedule::query()
            ->where('service_id', $service->id)
            ->whereBetween('start', [$request->date_range[0], $request->date_range[1]])
            ->orWhereBetween('end', [$request->date_range[0], $request->date_range[1]])
            ->exists();

        if ($exists) {
            abort(422, 'Já existem horários definidos neste intervalo de tempo');
        }
    }

    private function verifyIfRequestDatesAreUnique(Request $request)
    {
        $days = array_map(function($day){
            return $day['date'];
        }, $request->data);

        if (count($days) !== count(array_unique($days))) {
            abort(422, 'Dados inválidos. (Datas duplicadas)');
        }
    }

    private function verifyIfUsersAreValid(Request $request, Service $service){
        $service_users = $service->users()->pluck('id')->toArray();
        foreach ($request->data as $day) {
            foreach ($day['nurses'] as $nurse){
                if (!in_array($nurse['id'], $service_users)) {
                    abort(422, 'Dados inválidos. (Utilizadores inválidos)');
                }
            }
        }
    }

    private function createNewSchedule(Service $service, Request $request): Model
    {
        return $service->schedules()->create([
            'start' => Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->date_range[0])->startOfDay()->format('Y-m-d'),
            'end' => Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->date_range[1])->startOfDay()->format('Y-m-d'),
            'draft' => $request->draft
        ]);
    }

    public function createShiftUsers(Schedule $schedule, Request $request): bool
    {
        $data = [];

        foreach ($request->data as $day) {
            foreach ($day['nurses'] as $nurse) {
                if ($nurse['shift'] && $nurse['id']) {
                    $data[] = [
                        'user_id' => $nurse['id'],
                        'shift_id' => $nurse['shift'],
                        'schedule_id' => $schedule->id,
                        'date' => Carbon::createFromFormat('d/m/Y', $day['date'])->format('Y-m-d'),
                    ];
                }
            }
        }

        if(!count($data))
            return false;

        ShiftUser::query()->insert($data);
        return true;

    }


    /**
     * Display the specified resource.
     */
    public function show(Service $service, Schedule $schedule)
    {
        $schedule->load([
            'userShifts',
            'users',
            'shifts',
            'userShifts.user',
            'userShifts.shift',
        ]);
        return new ScheduleResource($schedule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Service $service, Schedule $schedule, Request $request){
        $request->validate([
            'draft' => ['required', 'boolean'],
            'data' => ['required', 'array'],
            'date_range' => ['required', 'array'],
            'date_range.*' => ['date'],
            'data.*.nurses_total' => ['required', 'numeric'],
            'data.*.date' => ['required'],
            'data.*.date_formatted' => ['required'],
        ]);

        // Verificar se já existe um horário no intervalo de datas, caso seja publicado
        if (!$request->draft) {
            $exists = Schedule::query()
                ->whereNot('id', $schedule->id)
                ->where('service_id', $service->id)
                ->where(function($query) use ($request){
                    $query->whereBetween('start', [$request->date_range[0], $request->date_range[1]]);
                    $query->orWhereBetween('end', [$request->date_range[0], $request->date_range[1]]);
                })
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Já existem horários definidos neste intervalo de tempo'], 422);
            }
        }

        // Verificar se as datas são únicas e os utilizadores são válidos
        $this->verifyIfRequestDatesAreUnique($request);
        $this->verifyIfUsersAreValid($request, $service);

        try {
            DB::beginTransaction();

            $schedule->start = $request->date_range[0];
            $schedule->end = $request->date_range[1];
            $schedule->draft = $request->draft;
            $schedule->saveOrFail();
            $exists = false;

            // Atualizar ou criar novos shift_users
            foreach ($request->data as $day) {
                foreach ($day['nurses'] as $nurse) {
                    $date = Carbon::createFromFormat('d/m/Y', $day['date'])->format('Y-m-d');
                    if ($nurse['shift'] && $nurse['id']) {
                        $schedule->userShifts()->updateOrCreate([
                            'user_id' => $nurse['id'],
                            'schedule_id' => $schedule->id,
                            'date' => $date,
                        ],
                        [
                            'shift_id' => $nurse['shift'],
                        ]);
                        $exists = true;
                    } else {
                        $schedule->userShifts()
                            ->where('user_id', $nurse['id'])
                            ->where('date', $date)
                            ->delete();
                    }
                }

                if(!$exists && !$request['draft']){
                    DB::rollBack();
                    return response()->json(['message' => 'Não pode lançar um horário sem registos'], 422);
                }
            }

            // Apagar todos os shift_users fora do range associado ao service
            $schedule->userShifts()
                ->where('date', '<', $schedule->start)
                ->orWhere('date', '>', $schedule->end)
                ->delete();

            DB::commit();
        } catch (\Throwable $t) {
            DB::rollBack();

            return response()->json(['message' => 'Erro ao processar pedido. Tente de novo', 'ex' => $t->getMessage()], 500);
        }

        return response()->json(['message' => 'Horário atualizado com sucesso']);
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
