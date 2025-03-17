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
                'prix'=> 100,
                'image_path'=>'logo.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_terrain' => 'Terrain B',
                'capacite' => '7v7',
                'type' => 'outdoor',
                'image_path'=>'logo.png',
                'prix'=> 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 