<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournoiTeamsSeeder extends Seeder
{
    public function run()
    {
        DB::table('tournoi_teams')->insert([
            [
                'id_tournoi' => 1,
                'team_name' => 'Winners FC',
                'descrption' => 'Local champions team',
                'capitain' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
} 