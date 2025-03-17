<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatchesSeeder extends Seeder
{
    public function run()
    {
        // First verify that we have the required tournament, teams and stage
        $tournoi = DB::table('tournoi')->first();
        $team1 = DB::table('tournoi_teams')->first();
        $team2 = DB::table('tournoi_teams')->skip(1)->first();
        $stage = DB::table('stages')->first();

        if ($tournoi && $team1 && $team2 && $stage) {
            DB::table('matches')->insert([
                [
                    'id_tournoi' => $tournoi->id_tournoi,
                    'team1_id' => $team1->id_teams,
                    'team2_id' => $team2->id_teams,
                    'match_date' => now()->addDays(1),
                    'score_team1' => 0,
                    'score_team2' => 0,
                    'stage' => $stage->id_stage,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }
    }
} 