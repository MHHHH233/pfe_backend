<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeletePendingReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:delete-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all pending reservations older than 1 hour';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get current time
        $oneHourAgo = Carbon::now()->subHour();
        
        // Delete all pending reservations older than 1 hour
        $deletedCount = Reservation::where('etat', 'en attente')
            ->where('created_at', '<', $oneHourAgo)
            ->delete();
            
        $this->info("Deleted $deletedCount pending reservations older than 1 hour");
        Log::info("Command executed: Deleted $deletedCount pending reservations older than 1 hour");
        
        return Command::SUCCESS;
    }
} 