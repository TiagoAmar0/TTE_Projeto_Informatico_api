<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clean tables
        DB::table('shifts')->truncate();
        DB::table('users')->truncate();
        DB::table('services')->truncate();

        // Insert data
        $this->call(ServiceSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ShiftSeeder::class);
    }
}
