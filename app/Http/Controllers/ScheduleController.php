<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ShiftUser;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Devolve a listagem de horários de um serviço
     */
    public function index(Service $service)
    {
        return ScheduleResource::collection($service->schedules()->get());
    }

    /**
     * Adiciona um novo horário a um serviço
     * @param Service $service
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Service $service, StoreScheduleRequest $request)
    {
        // Valida as diferentes restrições dos inputs
        $this->validateStoreRequest($request, $service);

        // Cria o objecto Schedule
        $schedule = $this->createNewSchedule($service, $request);

        // Popula o schedule com objetos ShiftUser
        $inserted = $this->createShiftUsers($schedule, $request);

        // Caso o horário esteja vazio e não seja ‘draft’, envia erro
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

    /**
     * Valida os parametros de entrada da função store
     * @param Request $request
     * @param Service $service
     * @return void
     */
    private function validateStoreRequest(Request $request, Service $service){
        // Caso não seja ‘draft’, verificar se já existe um horário publicado que se sobreponha a este
        // Se sim, envia erro visto que não podem existir horários sobrepostos
        if (!$request->draft) {
            $this->verifyIfAlreadyExistsPublishedScheduleInDateRange($request, $service);
        }

        // Verificar se não existem datas duplicadas nos inputs
        // Verificar se cada utilizador apenas tem ≤ 1 registos para cada dia (1 turno ou folga)
        $this->verifyIfThereIsNotDuplicatedDates($request);

        // Verifica se todos os utilizadores são válidos, ou seja, se são utilizadores do serviço
        $this->verifyIfUsersBelongToService($request, $service);
    }

    /**
     * Verifica se já existe um horário publicado que coincida com alguma data do horário a guardar
     * @param Request $request
     * @param Service $service
     * @return void
     */
    private function verifyIfAlreadyExistsPublishedScheduleInDateRange(Request $request, Service $service){
        $exists = Schedule::query()
            ->where('service_id', $service->id)
            ->whereBetween('start', [$request->date_range[0], $request->date_range[1]])
            ->orWhereBetween('end', [$request->date_range[0], $request->date_range[1]])
            ->exists();

        if ($exists) {
            abort(422, 'Já existem horários definidos neste intervalo de tempo');
        }
    }

    /**
     * Verifica se não existem registos de datas duplicadas
     * @param Request $request
     * @return void
     */
    private function verifyIfThereIsNotDuplicatedDates(Request $request)
    {
        $days = array_map(function($day){
            return $day['date'];
        }, $request->data);

        if (count($days) !== count(array_unique($days))) {
            abort(422, 'Dados inválidos. (Datas duplicadas)');
        }
    }

    /**
     * Valida se todos os utilizadores a quem se quer atribuir turnos pertencem ao serviço associado ao horário
     * @param Request $request
     * @param Service $service
     * @return void
     */
    private function verifyIfUsersBelongToService(Request $request, Service $service){
        $service_users = $service->users()->pluck('id')->toArray();
        foreach ($request->data as $day) {
            foreach ($day['nurses'] as $nurse){
                if (!in_array($nurse['id'], $service_users)) {
                    abort(422, 'Dados inválidos. (Utilizadores inválidos)');
                }
            }
        }
    }

    /**
     * Cria e devolve o objeto do Schedule
     * @param Service $service
     * @param Request $request
     * @return Model
     */
    private function createNewSchedule(Service $service, Request $request): Model
    {
        return $service->schedules()->create([
            'start' => Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->date_range[0])->startOfDay()->format('Y-m-d'),
            'end' => Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $request->date_range[1])->startOfDay()->format('Y-m-d'),
            'draft' => $request->draft
        ]);
    }

    /**
     * Cria os objetos que associam os utilizadores e turnos ao horário em questão
     * @param Schedule $schedule
     * @param Request $request
     * @return bool
     */
    public function createShiftUsers(Schedule $schedule, Request $request): bool
    {
        $data = [];

        // Percorre os dias do range de datas e adiciona a um array
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

        // Mass insert de todos os registos do horário
        ShiftUser::query()->insert($data);
        return true;
    }


    /**
     * Devolve o registo de um horário com os dados dos utilizadores e turnos respetivos
     * @param Service $service
     * @param Schedule $schedule
     * @return ScheduleResource
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
     * Atualiza os registos de um horário e as alocações dos utilizadores aos turnos
     * @param Service $service
     * @param Schedule $schedule
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Service $service, Schedule $schedule, StoreScheduleRequest $request){

        // Se o horário for atualizado em estado não ‘draft’ (publicado), verificar se já existem horários publicados que coincidam
        if (!$request->draft) {
            $exists = Schedule::query()
                ->whereNot('id', $schedule->id)
                ->where('service_id', $service->id)
                ->where('draft', false)
                ->where(function($query) use ($request){
                    $query->whereBetween('start', [$request->date_range[0], $request->date_range[1]]);
                    $query->orWhereBetween('end', [$request->date_range[0], $request->date_range[1]]);
                })
                ->exists();


            // Se existirem, devolve erro
            if ($exists) {
                return response()->json([
                    'message' => 'Já existem horários definidos neste intervalo de tempo',
                ], 422);
            }
        }

        // Verificar se as datas são únicas e se os utilizadores pertencem ao serviço
        $this->verifyIfThereIsNotDuplicatedDates($request);
        $this->verifyIfUsersBelongToService($request, $service);

        try {
            DB::beginTransaction();

            // Atualizar os dados do horário
            $schedule->start = $request->date_range[0];
            $schedule->end = $request->date_range[1];
            $schedule->draft = $request->draft;
            $schedule->saveOrFail();
            $exists = false;

            // Atualizar ou criar as associações entre utilizadores e turnos do horário
            foreach ($request->data as $day) {
                foreach ($day['nurses'] as $nurse) {
                    $date = Carbon::createFromFormat('d/m/Y', $day['date'])->format('Y-m-d');
                    // Caso o utilizador tenha um turno atribuído, cria ou atualiza o registo
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
                        // Caso contrário, elimina eventuais alocações para esse utilizador nessa data (está de folga)
                        $schedule->userShifts()
                            ->where('user_id', $nurse['id'])
                            ->where('date', $date)
                            ->delete();
                    }
                }
            }

            // Caso o horário tenha sido enviado em estado definitivo e esteja vazio, reverte as inserções e atualizações e emite erro
            if(!$exists && !$request['draft']){
                DB::rollBack();
                return response()->json(['message' => 'Não pode lançar um horário sem registos'], 422);
            }

            // Apaga todos os eventuais registos criados para o horário fora das datas
            // Acontece quando se altera o range e os dias excluídos tinham dados
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
     * Elimina o registo de um horário
     * @param Service $service
     * @param Schedule $schedule
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Service $service, Schedule $schedule)
    {
        // Apagar as alocações de turnos
        $schedule->userShifts()->delete();

        // Apagar o horário
        $schedule->delete();

        return response()->json([
            'message' => 'O horário foi eliminado'
        ]);
    }

    /**
     * Exporta o horário para uma tabela em PDF
     * @param Service $service
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function export(Service $service, Schedule $schedule)
    {
        // Carregar os dados dos utilizadores, turnos e alocações de turnos com o horário
        $schedule->load([
            'userShifts',
            'users',
            'shifts',
            'userShifts.user',
            'userShifts.shift',
        ]);

        $daysOfWeek = [
            'Dom',
            'Seg',
            'Ter',
            'Qua',
            'Qui',
            'Sex',
            'Sab'
        ];

        // Obter array com as datas que estão entre o início e o fim do horário
        $dates = $this->getDatesInRange($schedule->start, $schedule->end);
        $tableData = [];

        // Percorrer datas e gerar o objeto que permita ser manipulado pela  blade template que vai gerar a tabela
        foreach ($dates as $i => $date){

            $dateObject = Carbon::createFromFormat('d/m/Y', $date);

            $tableData[$i] = [
                'date' => $date,
                'day' => $dateObject->format('d'),
                'month' => $dateObject->format('M'),
                'day_of_the_week' => $daysOfWeek[$dateObject->dayOfWeek],
                // Mapeia as alocações para um array que contenha o nome do turno e o nome do utilizador que tenha turno alocado na respetiva data
                'nurses' => $schedule->userShifts->map(function ($user_shift) use ($dateObject) {
                    $shiftDate = Carbon::parse($user_shift->date);
                    if ($shiftDate->isSameDay($dateObject)) {
                        return [
                            'shift' => $user_shift->shift->name,
                            'user' => $user_shift->user_id
                        ];
                    }
                    return false;
                })->filter()
            ];

            // Calcular total de enfermeiros alocados no dia
            $tableData[$i]['nurses_total'] = count($tableData[$i]['nurses']);

            // Calcular o total de enfermeiros alocados por turno
            $tableData[$i]['shifts'] = $schedule->shifts->map(function ($shift) use ($tableData, $i) {
                return [
                    'shift' => $shift->name,
                    'total' => collect($tableData[$i]['nurses'])
                        ->filter(function ($shiftUser) use ($shift) {
                            return $shiftUser['shift'] === $shift->name;
                        })
                        ->count()
                ];
            })->filter();
        }

        $start = Carbon::parse($schedule->start);
        $end = Carbon::parse($schedule->end);

        // Gerar o PDF através de uma Blade View
        $pdf = Pdf::loadView('exports.scheduleExport', [
            'data' => $tableData,
            'users' => $schedule->users()->orderBy('name')->get(),
            'service' => $service->name,
            'start_day' => $start->day,
            'start_month' => $start->monthName,
            'end_day' => $end->day,
            'end_month' => $end->monthName,
            'end' => Carbon::parse($schedule->end)->format('d M')
        ])->setPaper('a4', 'landscape');


        // Devolve o PDF para download
        return $pdf->download();
    }

    /**
     * Recebe uma data inicial e uma data final e devolve um array de todas as datas daquele intervalo
     * @param $start
     * @param $end
     * @return array
     */
    private function getDatesInRange($start, $end) : array
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $dates = [];

        while ($startDate->lte($endDate)) {
            $dates[] = $startDate->format('d/m/Y');
            $startDate->addDay();
        }

        return $dates;
    }
}
