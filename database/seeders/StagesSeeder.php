<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StagesSeeder extends Seeder
{
    public function run()
    {
        DB::table('stages')->insert([
            [
                'stage_name' => 'Beginner Stage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'stage_name' => 'Intermediate Stage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'stage_name' => 'Advanced Stage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 