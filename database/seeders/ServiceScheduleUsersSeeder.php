<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\Shift;
use App\Models\ShiftUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ServiceScheduleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create service
        $service = Service::query()->create([
            'name' => 'Análises Clínicas'
        ]);

        // Create nurses
        User::query()->insert([
            'name' => 'João',
            'email' => 'joao@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Carolina',
            'email' => 'carolina@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'lead-nurse'
        ]);

        User::query()->insert([
            'name' => 'Constança',
            'email' => 'constanca@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Francisco',
            'email' => 'francisco@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'António',
            'email' => 'antonio@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Maria',
            'email' => 'maria@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Carmo',
            'email' => 'carmo@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Bruna',
            'email' => 'bruna@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Rodrigo',
            'email' => 'rodrigo@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Adriano',
            'email' => 'adriano@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Miguel',
            'email' => 'miguel@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Tiago',
            'email' => 'tiago@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Fernando',
            'email' => 'fernando@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Kevin',
            'email' => 'kevin@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Diogo',
            'email' => 'diogo@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Samuel',
            'email' => 'samuel@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Gonçalo',
            'email' => 'goncalo@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Bernardo',
            'email' => 'bernardo@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Catarina',
            'email' => 'catarina@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        User::query()->insert([
            'name' => 'Helena',
            'email' => 'helena@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => $service->id,
            'type' => 'nurse'
        ]);

        $users = User::query()->where('service_id', $service->id)->get();
        $start_date = Carbon::now();
        $end_date = Carbon::now()->addDays(28);

        $schedule = Schedule::query()->create([
            'start' => $start_date->format('Y-m-d'),
            'end' => $end_date->format('Y-m-d'),
            'service_id' => $service->id,
            'draft' => false,
        ]);

        $morning = Shift::query()->create([
            'service_id' => $service->id,
            'name' => 'M',
            'description' => 'Manhã',
            'start' => '00:00:00',
            'end' => '07:59:00',
            'minutes' => 8 * 60,
            'nurses_qty' => 2,
        ]);

        $afternoon = Shift::query()->create([
            'service_id' => $service->id,
            'name' => 'T',
            'description' => 'Tarde',
            'start' => '08:00:00',
            'end' => '15:59:00',
            'minutes' => 8 * 60,
            'nurses_qty' => 1,
        ]);

        $night = Shift::query()->create([
            'service_id' => $service->id,
            'name' => 'N',
            'description' => 'Noite',
            'start' => '16:00:00',
            'end' => '23:59:00',
            'minutes' => 8 * 60,
            'nurses_qty' => 1,
        ]);

        $index_folga = 0;
        $total = $users->count();

        $shifts = [
            $morning->id,
            $morning->id,
            $morning->id,
            $morning->id,
            $afternoon->id,
            $afternoon->id,
            $afternoon->id,
            $afternoon->id,
            $night->id,
            $night->id,
            $night->id,
            $night->id,
        ];

        echo "Shifts: ". count($shifts) . "\n";
        echo "Users: ". $users->count() . "\n";

        while ($start_date->isBefore($end_date)){
            $users = $users->shuffle();
            echo "\nDay " . $start_date->format('d-m-Y') . "\n\n";
            $shift_index = 0;
            foreach ($users as $i => $user){
                if($i < count($shifts)){
                    ShiftUser::query()->insert([
                        'user_id' => $user->id,
                        'shift_id' => $shifts[$shift_index],
                        'schedule_id' => $schedule->id,
                        'date' => $start_date
                    ]);
                    echo $user->name . " " .$shift_index . " shift: " . $shifts[$shift_index] . "\n";
                    $shift_index++;
                }
            }

//            if($index_folga != $total - 1){
//                $index_folga++;
//            } else {
//                $index_folga = 0;
//            }

            $start_date = $start_date->addDay();
        }
    }
}
