<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Delete pending reservations older than 1 hour every 15 minutes
        $schedule->call(function () {
            $oneHourAgo = Carbon::now()->subHour();
            
            $deletedCount = Reservation::where('etat', 'en attente')
                ->where('created_at', '<', $oneHourAgo)
                ->delete();
                
            Log::info('Scheduled task: Deleted ' . $deletedCount . ' expired pending reservations older than 1 hour');
        })->everyFifteenMinutes();
        
        // Delete past reservations every day at midnight
        $schedule->call(function () {
            $deletedCount = Reservation::where(DB::raw("CONCAT(date, ' ', heure)"), '<', now())->delete();
            
            Log::info('Scheduled task: Deleted ' . $deletedCount . ' past reservations');
        })->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 