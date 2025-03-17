<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerRequestSeeder extends Seeder
{
    public function run()
    {
        // First verify that we have the required players
        $player1 = DB::table('players')->first();
        $player2 = DB::table('players')->skip(1)->first();

        if ($player1 && $player2) {
            DB::table('player_request')->insert([
                [
                    'sender' => $player1->id_player,
                    'receiver' => $player2->id_player,
                    'match_date' => now(),
                    'starting_time' => '14:00:00',
                    'message' => 'Would you like to play a match?',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
} 