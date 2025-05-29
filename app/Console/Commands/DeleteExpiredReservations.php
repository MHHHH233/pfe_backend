<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cleanup {--all : Delete all past reservations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired pending reservations and optionally all past reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Delete pending reservations older than 1 hour
        $oneHourAgo = Carbon::now()->subHour();
        
        $deletedPendingCount = Reservation::where('etat', 'en attente')
            ->where('created_at', '<', $oneHourAgo)
            ->delete();
            
        $this->info("Deleted $deletedPendingCount expired pending reservations older than 1 hour");
        Log::info("Manual cleanup: Deleted $deletedPendingCount expired pending reservations");
        
        // If --all flag is provided, delete all past reservations
        if ($this->option('all')) {
            $deletedPastCount = Reservation::where(DB::raw("CONCAT(date, ' ', heure)"), '<', now())->delete();
            
            $this->info("Deleted $deletedPastCount past reservations");
            Log::info("Manual cleanup: Deleted $deletedPastCount past reservations");
        }
        
        return Command::SUCCESS;
    }
} 