<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademieSeeder extends Seeder
{
    public function run()
    {
        DB::table('academie')->insert([
            [
                'nom' => 'Football Academy',
                'description' => 'Premier football academy',
                'date_creation' => now(),
                'plan_base' => 100.00,
                'plan_premium' => 200.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 