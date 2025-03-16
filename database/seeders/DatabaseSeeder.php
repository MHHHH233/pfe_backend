<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,           // Create roles first
            CompteSeeder::class,         // Then create accounts
            TerrainSeeder::class,        // Create terrains
            StagesSeeder::class,         // Create stages
            AcademieSeeder::class,       // Create academies
            AcademieCoachSeeder::class,  // Create coaches
            PlayersSeeder::class,        // Create players
            TeamsSeeder::class,          // Create teams
            AcademieProgrammeSeeder::class,    // Create programmes (needs academie)
            AcademieActivitesSeeder::class,    // Create activities (needs academie)
            ActivitesMembersSeeder::class,     // Create activity members (needs compte and activities)
            ReviewsSeeder::class,        // Create reviews
            AnalyticsSeeder::class,      // Create analytics
        ]);
    }
}
