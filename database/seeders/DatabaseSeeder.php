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
            RoleSeeder::class,           
            CompteSeeder::class,         // Create accounts first
            PlayersSeeder::class,        // Then create players
            TerrainSeeder::class,        
            StagesSeeder::class,         
            AcademieSeeder::class,       
            AcademieCoachSeeder::class,  
            TeamsSeeder::class,          
            AcademieProgrammeSeeder::class,    
            AcademieActivitesSeeder::class,    
            ActivitesMembersSeeder::class,     
            ReviewsSeeder::class,        
            AnalyticsSeeder::class,      
            TournoiSeeder::class,        
            TournoiTeamsSeeder::class,   
            PlayerRequestSeeder::class,   // This should come after PlayersSeeder
            MatchesSeeder::class,        // Added Matches seeder
            ReportedBugSeeder::class,
        ]);
    }
}
