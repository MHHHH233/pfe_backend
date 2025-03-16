<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademieCoachSeeder extends Seeder
{
    public function run()
    {
        DB::table('academie_coach')->insert([
            [
                'id_academie' => 1,
                'nom' => 'John Coach',
                'pfp' => 'coach1.jpg',
                'description' => 'Experienced football coach',
                'instagram' => '@johncoach',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_academie' => 1,
                'nom' => 'Sarah Coach',
                'pfp' => 'coach2.jpg',
                'description' => 'Professional youth trainer',
                'instagram' => '@sarahcoach',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 