<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PlayerRequest;

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
                    'match_date' => Carbon::tomorrow(),
                    'starting_time' => '14:00:00',
                    'message' => 'Would you like to play a match?',
                    'status' => PlayerRequest::STATUS_PENDING,
                    'expires_at' => Carbon::now()->addHours(24),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'sender' => $player2->id_player,
                    'receiver' => $player1->id_player,
                    'match_date' => Carbon::tomorrow()->addDays(1),
                    'starting_time' => '15:00:00',
                    'message' => 'Available for a game?',
                    'status' => PlayerRequest::STATUS_ACCEPTED,
                    'expires_at' => Carbon::now()->addHours(24),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
} 