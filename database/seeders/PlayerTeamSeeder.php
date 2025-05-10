<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Players;
use App\Models\Teams;
use App\Models\PlayerTeam;

class PlayerTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all players and teams
        $players = Players::all();
        $teams = Teams::all();
        
        if ($players->isEmpty() || $teams->isEmpty()) {
            return;
        }
        
        // Make sure team captains are in their own teams
        foreach ($teams as $team) {
            if ($team->capitain) {
                DB::table('player_team')->updateOrInsert(
                    [
                        'id_player' => $team->capitain,
                        'id_teams' => $team->id_teams
                    ],
                    [
                        'status' => PlayerTeam::STATUS_ACCEPTED, // Captains are auto-accepted
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
        
        // Add some random players to teams
        // Each player can be in 0-2 teams
        foreach ($players as $player) {
            // Skip if player is already a captain
            if (Teams::where('capitain', $player->id_player)->exists()) {
                continue;
            }
            
            // Randomly decide if player joins a team (70% chance)
            if (rand(1, 10) <= 7) {
                // Select 1-2 random teams
                $numTeams = rand(1, 2);
                $randomTeams = $teams->random(min($numTeams, $teams->count()));
                
                foreach ($randomTeams as $team) {
                    // Check if player is already in this team (as captain or member)
                    $alreadyInTeam = DB::table('player_team')
                        ->where('id_player', $player->id_player)
                        ->where('id_teams', $team->id_teams)
                        ->exists();
                        
                    if (!$alreadyInTeam) {
                        // 80% chance to be accepted, 10% pending, 10% refused
                        $statusRand = rand(1, 10);
                        $status = PlayerTeam::STATUS_ACCEPTED;
                        
                        if ($statusRand == 9) {
                            $status = PlayerTeam::STATUS_PENDING;
                        } elseif ($statusRand == 10) {
                            $status = PlayerTeam::STATUS_REFUSED;
                        }
                        
                        DB::table('player_team')->insert([
                            'id_player' => $player->id_player,
                            'id_teams' => $team->id_teams,
                            'status' => $status,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        }
    }
} 