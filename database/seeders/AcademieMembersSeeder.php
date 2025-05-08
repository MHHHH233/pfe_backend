<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademieMembers;
use App\Models\Academie;
use App\Models\Compte;
use Carbon\Carbon;

class AcademieMembersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all academies
        $academies = Academie::all();
        if ($academies->isEmpty()) {
            return;
        }

        // Get some accounts (users)
        $comptes = Compte::take(10)->get();
        if ($comptes->isEmpty()) {
            return;
        }

        $subscriptionPlans = ['base', 'premium'];
        
        // Create memberships
        foreach ($comptes as $index => $compte) {
            // Assign each user to at least one academy
            $academyIndex = $index % $academies->count();
            $academy = $academies[$academyIndex];
            
            AcademieMembers::create([
                'id_compte' => $compte->id_compte,
                'id_academie' => $academy->id_academie,
                'status' => 'active',
                'subscription_plan' => $subscriptionPlans[$index % 2],
                'date_joined' => Carbon::now()->subDays(rand(1, 60))
            ]);
            
            // Add some users to multiple academies
            if ($index < 5) {
                $secondAcademyIndex = ($academyIndex + 1) % $academies->count();
                $secondAcademy = $academies[$secondAcademyIndex];
                
                AcademieMembers::create([
                    'id_compte' => $compte->id_compte,
                    'id_academie' => $secondAcademy->id_academie,
                    'status' => 'active',
                    'subscription_plan' => $subscriptionPlans[($index + 1) % 2],
                    'date_joined' => Carbon::now()->subDays(rand(1, 30))
                ]);
            }
        }
    }
} 