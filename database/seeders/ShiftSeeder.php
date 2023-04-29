<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = Service::all();

        foreach($services as $service){
            Shift::query()->create([
                'service_id' => $service->id,
                'name' => 'M',
                'start' => '00:00:00',
                'end' => '00:07:59',
                'minutes' => 8 * 60,
                'nurses_qty' => 2,
            ]);

            Shift::query()->create([
                'service_id' => $service->id,
                'name' => 'T',
                'start' => '00:08:00',
                'end' => '00:15:59',
                'minutes' => 8 * 60,
                'nurses_qty' => 1,
            ]);

            Shift::query()->create([
                'service_id' => $service->id,
                'name' => 'N',
                'start' => '00:16:00',
                'end' => '00:23:59',
                'minutes' => 8 * 60,
                'nurses_qty' => 1,
            ]);
        }
    }
}
