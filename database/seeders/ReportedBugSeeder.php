<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReportedBug;
use App\Models\Compte;

class ReportedBugSeeder extends Seeder
{
    public function run()
    {
        // Get some compte IDs to use
        $compteIds = Compte::pluck('id_compte')->toArray();

        if (empty($compteIds)) {
            // Create a default compte if none exists
            $compte = Compte::factory()->create();
            $compteIds = [$compte->id_compte];
        }

        // Create sample bugs with different statuses
        ReportedBug::create([
            'id_compte' => $compteIds[array_rand($compteIds)],
            'description' => 'Login button not working properly',
            'status' => ReportedBug::STATUS_PENDING
        ]);

        ReportedBug::create([
            'id_compte' => $compteIds[array_rand($compteIds)],
            'description' => 'App crashes when uploading large images',
            'status' => ReportedBug::STATUS_IN_PROGRESS
        ]);

        ReportedBug::create([
            'id_compte' => $compteIds[array_rand($compteIds)],
            'description' => 'Error in payment processing',
            'status' => ReportedBug::STATUS_RESOLVED
        ]);

        ReportedBug::create([
            'id_compte' => $compteIds[array_rand($compteIds)],
            'description' => 'Invalid bug report',
            'status' => ReportedBug::STATUS_REJECTED
        ]);
    }
} 