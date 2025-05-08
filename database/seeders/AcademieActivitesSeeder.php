<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademieActivitesSeeder extends Seeder
{
    public function run()
    {
        DB::table('academie_activites')->insert([
            [
                'id_academie' => 1,
                'title' => 'Summer Camp',
                'description' => 'Intensive summer training program',
                'date_debut' => now(),
                'date_fin' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_academie' => 1,
                'title' => ' Winter23423 Camp',
                'description' => 'Intensive winter training program',
                'date_debut' => now(),
                'date_fin' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],  
            [
                'id_academie' => 1,
                'title' => 'Winter3456 Camp',
                'description' => 'Intensive winter34535 training program',
                'date_debut' => now(),
                'date_fin' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_academie' => 1,
                'title' => 'asf Camp',
                'description' => 'Intensive asf training program',
                'date_debut' => now(),
                'date_fin' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 