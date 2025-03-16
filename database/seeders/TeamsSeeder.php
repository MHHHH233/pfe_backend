<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamsSeeder extends Seeder
{
    public function run()
    {
        DB::table('teams')->insert([
            [
                'capitain' => 1, // References id_compte from compte table
                'total_matches' => 0,
                'rating' => 0,
                'starting_time' => '09:00:00',
                'finishing_time' => '18:00:00',
                'misses' => 0,
                'invites_accepted' => 0,
                'invites_refused' => 0,
                'total_invites' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 