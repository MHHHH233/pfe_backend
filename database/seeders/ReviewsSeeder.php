<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reviews;

class ReviewsSeeder extends Seeder
{
    public function run()
    {
        // Create sample reviews with different statuses
        Reviews::create([
            'id_compte' => 1,
            'name' => 'Great Service',
            'description' => 'The service was excellent and the staff was very friendly.',
            'status' => Reviews::STATUS_APPROVED
        ]);

        Reviews::create([
            'id_compte' => 1,
            'name' => 'Good Experience',
            'description' => 'Had a good time, but there is room for improvement.',
            'status' => Reviews::STATUS_PENDING
        ]);

        Reviews::create([
            'id_compte' => 1,
            'name' => 'Average Service',
            'description' => 'The service was okay but could be better.',
            'status' => Reviews::STATUS_APPROVED
        ]);

        Reviews::create([
            'id_compte' => 1,
            'name' => 'Inappropriate Content',
            'description' => 'This review contains inappropriate content.',
            'status' => Reviews::STATUS_REJECTED
        ]);
    }
} 