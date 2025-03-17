<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayersSeeder extends Seeder
{
    public function run()
    {
        DB::table('players')->insert([
            [
                'id_compte' => 1,
                'position' => 'Forward',
                'total_matches' => 10,
                'rating' => 4,
                'starting_time' => '09:00:00',
                'finishing_time' => '18:00:00',
                'misses' => 0,
                'invites_accepted' => 5,
                'invites_refused' => 2,
                'total_invites' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_compte' => 2,
                'position' => 'Midfielder',
                'total_matches' => 8,
                'rating' => 4,
                'starting_time' => '10:00:00',
                'finishing_time' => '19:00:00',
                'misses' => 1,
                'invites_accepted' => 4,
                'invites_refused' => 1,
                'total_invites' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 