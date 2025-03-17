<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournoiSeeder extends Seeder
{
    public function run()
    {
        DB::table('tournoi')->insert([
            [
                'name' => 'Summer Tournament 2024',
                'description' => 'Annual summer football tournament',
                'capacite' => 16,
                'type' => '5v5',
                'date_debut' => now(),
                'date_fin' => now()->addDays(7),
                'frais_entree' => 100.00,
                'award' => 1000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
} 