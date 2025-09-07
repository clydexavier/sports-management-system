<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class IntramuralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('intramural_games')->insert(
        [
            'name' => 'Collegiate',
            'location' => 'VSU - Baybay',
            'status' => 'in progress',
            'start_date' => '2025-09-30',
            'end_date' => '2025-10-05'
        ], 
        [
            'name' => 'High School',
            'location' => 'VSU - Baybay',
            'status' => 'in progress',
            'start_date' => '2025-09-30',
            'end_date' => '2025-10-05',
        ],
    );
    }
}