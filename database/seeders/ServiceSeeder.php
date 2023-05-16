<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('services')->insert([
            'name' => 'Pediatria'
        ]);

        DB::table('services')->insert([
            'name' => 'Ortopedia'
        ]);

        DB::table('services')->insert([
            'name' => 'Medicina Geral'
        ]);



        DB::table('services')->insert([
            'name' => 'Arritmologia'
        ]);

        DB::table('services')->insert([
            'name' => 'Atendimento Urgente'
        ]);

        DB::table('services')->insert([
            'name' => 'Cardiologia'
        ]);

        DB::table('services')->insert([
            'name' => 'Cardiologia Pediátrica'
        ]);
        DB::table('services')->insert([
            'name' => 'Cefaleias'
        ]);
    }
}
