<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cleanup-future';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up reservations with future created_at dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        
        // Find pending reservations with future created_at dates
        $futureReservations = Reservation::where('etat', 'en attente')
            ->where('created_at', '>', $now->copy()->addDay())
            ->get();
            
        $this->info("Found " . $futureReservations->count() . " pending reservations with future created_at dates");
        
        foreach ($futureReservations as $reservation) {
            $this->info("ID: {$reservation->id_reservation}, Created at: {$reservation->created_at}, Date: {$reservation->date}, Time: {$reservation->heure}");
        }
        
        if ($this->confirm('Do you want to delete these reservations?')) {
            $deletedCount = Reservation::where('etat', 'en attente')
                ->where('created_at', '>', $now->copy()->addDay())
                ->delete();
                
            $this->info("Deleted $deletedCount reservations with future created_at dates");
            Log::info("Manual cleanup: Deleted $deletedCount reservations with future created_at dates");
        }
        
        return Command::SUCCESS;
    }
} 