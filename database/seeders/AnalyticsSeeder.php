<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    public function run()
    {
        DB::table('analytics')->insert([
            [
                'analytic_name' => 'Total Users',
                'total' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'analytic_name' => 'Active Players',
                'total' => 75,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 