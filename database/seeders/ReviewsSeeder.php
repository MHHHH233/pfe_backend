<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewsSeeder extends Seeder
{
    public function run()
    {
        DB::table('reviews')->insert([
            [
                'name' => 'John Doe',
                'description' => 'Great experience with the academy!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'description' => 'Excellent coaching staff',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 