<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademieProgrammeSeeder extends Seeder
{
    public function run()
    {
        DB::table('academie_programme')->insert([
            [
                'id_academie' => 1,
                'jour' => 'Monday',
                'horaire' => '09:00:00',
                'programme' => 'Basic Training',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_academie' => 1,
                'jour' => 'Wednesday',
                'horaire' => '14:00:00',
                'programme' => 'Advanced Training',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 