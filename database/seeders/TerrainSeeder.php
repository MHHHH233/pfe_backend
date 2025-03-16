<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TerrainSeeder extends Seeder
{
    public function run()
    {
        DB::table('terrain')->insert([
            [
                'nom_terrain' => 'Terrain A',
                'capacite' => '5v5',
                'type' => 'indoor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_terrain' => 'Terrain B',
                'capacite' => '7v7',
                'type' => 'outdoor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 