<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        DB::table('users')->insert([
            'name' => 'admin',
            'email' => 'admin@tte.pt',
            'password' => Hash::make('password'),
            'service_id' => null,
            'type' => 'admin'
        ]);

        $first_service = Service::first()->id;

        for ($i = 0; $i < 100; $i++) {
            if($i < 5){
                $type = 'admin';
                $service = null;
            } elseif ($i < 15){
                $type = 'lead-nurse';
                $service = null;
            } else {
                if($i < 25){
                    $service = $first_service;
                } else {
                    $service = null;
                }
                $type = 'nurse';
            }

            DB::table('users')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'service_id' => $service,
                'type' => $type
            ]);
        }
    }
}
