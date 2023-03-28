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
            'service_id' => Service::first()->id,
            'type' => 'admin'
        ]);

        for ($i = 0; $i < 100; $i++) {
            if($i < 5){
                $type = 'admin';
            } elseif ($i < 15){
                $type = 'lead-nurse';
            } else {
                $type = 'nurse';
            }

            DB::table('users')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'service_id' => Service::first()->id,
                'type' => $type
            ]);
        }
    }
}
