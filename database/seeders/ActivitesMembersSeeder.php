<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitesMembersSeeder extends Seeder
{
    public function run()
    {
        DB::table('activites_members')->insert([
            [
                'id_member_ref' => 1,
                'id_activites' => 1,
                'date_joined' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_member_ref' => 2,
                'id_activites' => 1,
                'date_joined' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}